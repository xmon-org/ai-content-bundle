<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Traits;

/**
 * Trait for parsing Pollinations API error responses.
 *
 * Pollinations API sometimes wraps underlying errors (like 429 rate limit)
 * inside HTTP 500 responses. This trait provides methods to detect these
 * wrapped errors so providers can handle them appropriately.
 */
trait PollinationsErrorParserTrait
{
    /**
     * Check if an error message contains a wrapped rate limit (429).
     *
     * Pollinations sometimes returns HTTP 500 with a JSON body that contains
     * a nested "cause" with statusCode 429 (rate limit). This method detects
     * that pattern so we can skip to the next model instead of retrying.
     *
     * Example error body:
     * {"success":false,"error":{...,"cause":{"statusCode":429,...}},"status":500}
     */
    private function isWrappedRateLimit(string $errorMessage): bool
    {
        // Look for JSON in the error message
        if (!str_contains($errorMessage, '{')) {
            return false;
        }

        // Extract JSON from the error message (it may be prefixed with text)
        if (preg_match('/\{.*\}/s', $errorMessage, $matches)) {
            $jsonString = $matches[0];

            try {
                $data = json_decode($jsonString, true, 512, \JSON_THROW_ON_ERROR);

                // Check for nested cause.statusCode = 429
                if (isset($data['error']['cause']['statusCode']) && $data['error']['cause']['statusCode'] === 429) {
                    return true;
                }

                // Also check direct cause (some responses have flatter structure)
                if (isset($data['cause']['statusCode']) && $data['cause']['statusCode'] === 429) {
                    return true;
                }
            } catch (\JsonException) {
                // Not valid JSON, ignore
            }
        }

        // Fallback: simple string check for common rate limit patterns
        return str_contains($errorMessage, '"statusCode":429')
            || str_contains($errorMessage, '"statusCode": 429');
    }
}
