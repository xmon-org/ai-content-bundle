<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Image;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\ImageResult;
use Xmon\AiContentBundle\Provider\Traits\PollinationsErrorParserTrait;

class PollinationsImageProvider
{
    use PollinationsErrorParserTrait;
    private const BASE_URL = 'https://gen.pollinations.ai/image/';
    private const PROVIDER_NAME = 'pollinations';

    /**
     * @param array<string> $fallbackModels
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $apiKey = null,
        private readonly string $model = 'flux',
        private readonly array $fallbackModels = [],
        private readonly int $retriesPerModel = 2,
        private readonly int $retryDelay = 3,
        private readonly int $timeout = 120,
        private readonly int $defaultWidth = 1280,
        private readonly int $defaultHeight = 720,
        private readonly string $quality = 'high',
        private readonly string $negativePrompt = 'worst quality, blurry, text, letters, watermark, human faces, detailed faces',
        private readonly bool $private = true,
        private readonly bool $nofeed = true,
    ) {
    }

    public function getName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function isAvailable(): bool
    {
        // Pollinations works without API key (with rate limits)
        // With API key, no rate limits
        return true;
    }

    /**
     * Get the provider configuration for debugging.
     *
     * @return array{
     *     model: string,
     *     fallback_models: array<string>,
     *     retries_per_model: int,
     *     retry_delay: int,
     *     timeout: int,
     *     width: int,
     *     height: int,
     *     quality: string,
     *     private: bool,
     *     nofeed: bool,
     *     has_api_key: bool
     * }
     */
    public function getConfig(): array
    {
        return [
            'model' => $this->model,
            'fallback_models' => $this->fallbackModels,
            'retries_per_model' => $this->retriesPerModel,
            'retry_delay' => $this->retryDelay,
            'timeout' => $this->timeout,
            'width' => $this->defaultWidth,
            'height' => $this->defaultHeight,
            'quality' => $this->quality,
            'private' => $this->private,
            'nofeed' => $this->nofeed,
            'has_api_key' => !empty($this->apiKey),
        ];
    }

    /**
     * Generate an image with fallback models and per-model retries.
     *
     * @param array{
     *     width?: int,
     *     height?: int,
     *     model?: string,
     *     seed?: int,
     *     nologo?: bool,
     *     enhance?: bool,
     *     safe?: bool,
     *     quality?: string,
     *     negative_prompt?: string,
     *     private?: bool,
     *     nofeed?: bool,
     *     use_fallback?: bool,
     *     timeout?: int,
     *     retries_per_model?: int,
     *     retry_delay?: int
     * } $options
     *
     * @throws AiProviderException When all models fail after retries
     */
    public function generate(string $prompt, array $options = []): ImageResult
    {
        $specificModel = $options['model'] ?? null;

        // Determine if fallback should be used:
        // - If no model specified: use fallback (default behavior)
        // - If model specified: don't use fallback unless explicitly requested
        $useFallback = $options['use_fallback'] ?? ($specificModel === null);

        // Build list of models to try
        $primaryModel = $specificModel ?? $this->model;
        $modelsToTry = $useFallback
            ? array_unique(array_merge([$primaryModel], $this->fallbackModels))
            : [$primaryModel];

        // Allow per-request override of retry settings
        $retriesPerModel = $options['retries_per_model'] ?? $this->retriesPerModel;
        $retryDelay = $options['retry_delay'] ?? $this->retryDelay;

        $errors = [];

        foreach ($modelsToTry as $model) {
            for ($attempt = 1; $attempt <= $retriesPerModel; ++$attempt) {
                try {
                    $this->logger?->info('[Pollinations] Trying model', [
                        'model' => $model,
                        'attempt' => $attempt,
                        'max_attempts' => $retriesPerModel,
                    ]);

                    return $this->doGenerate($prompt, $model, $options);
                } catch (AiProviderException $e) {
                    $this->logger?->warning('[Pollinations] Attempt failed', [
                        'model' => $model,
                        'attempt' => $attempt,
                        'max_attempts' => $retriesPerModel,
                        'error' => $e->getMessage(),
                        'http_status' => $e->getHttpStatusCode(),
                    ]);

                    // Don't retry on client errors (4xx) or wrapped rate limits (500 with 429 cause)
                    if ($e->getHttpStatusCode() !== null && $e->getHttpStatusCode() >= 400 && $e->getHttpStatusCode() < 500) {
                        $errors[$model] = $e->getMessage();
                        break; // Skip to next model
                    }

                    // Check if this is a 500 wrapping a rate limit (429)
                    if ($e->getHttpStatusCode() === 500 && $this->isWrappedRateLimit($e->getMessage())) {
                        $this->logger?->warning('[Pollinations] Detected wrapped rate limit (500 with 429 cause)', [
                            'model' => $model,
                        ]);
                        $errors[$model] = $e->getMessage();
                        break; // Skip to next model instead of retrying
                    }

                    // Wait before retry (except on last attempt of this model)
                    if ($attempt < $retriesPerModel) {
                        sleep($retryDelay);
                    } else {
                        $errors[$model] = $e->getMessage();
                    }
                }
            }
        }

        // All models exhausted
        $errorDetails = implode('; ', array_map(
            fn (string $model, string $error) => \sprintf('%s: %s', $model, $error),
            array_keys($errors),
            array_values($errors)
        ));

        throw new AiProviderException(message: \sprintf('All image models failed: %s', $errorDetails ?: 'No models available'), provider: self::PROVIDER_NAME);
    }

    /**
     * Execute a single image generation request.
     */
    private function doGenerate(string $prompt, string $model, array $options): ImageResult
    {
        $width = $options['width'] ?? $this->defaultWidth;
        $height = $options['height'] ?? $this->defaultHeight;
        $seed = $options['seed'] ?? null;
        $nologo = $options['nologo'] ?? ($this->apiKey !== null);
        $enhance = $options['enhance'] ?? false;
        $safe = $options['safe'] ?? false;
        $quality = $options['quality'] ?? $this->quality;
        $negativePrompt = $options['negative_prompt'] ?? $this->negativePrompt;
        $private = $options['private'] ?? $this->private;
        $nofeed = $options['nofeed'] ?? $this->nofeed;
        $timeout = $options['timeout'] ?? $this->timeout;

        // Build URL with encoded prompt
        $encodedPrompt = $this->encodePrompt($prompt);
        $url = self::BASE_URL.$encodedPrompt;

        // Build query parameters
        $params = [
            'width' => $width,
            'height' => $height,
            'model' => $model,
            'quality' => $quality,
            'nologo' => $nologo ? 'true' : 'false',
            'enhance' => $enhance ? 'true' : 'false',
            'safe' => $safe ? 'true' : 'false',
            'private' => $private ? 'true' : 'false',
            'nofeed' => $nofeed ? 'true' : 'false',
        ];

        if ($seed !== null) {
            $params['seed'] = $seed;
        }

        if (!empty($negativePrompt)) {
            $params['negative_prompt'] = $negativePrompt;
        }

        $url .= '?'.http_build_query($params);

        // Log the EXACT request URL for debugging
        $this->logger?->info('[Pollinations] REQUEST URL', [
            'url' => $url,
            'has_api_key' => $this->apiKey !== null,
        ]);

        $this->logger?->debug('[Pollinations] Generating image', [
            'model' => $model,
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'seed' => $seed,
            'private' => $private,
            'has_negative_prompt' => !empty($negativePrompt),
            'prompt_length' => \strlen($prompt),
        ]);

        // Build request options
        $requestOptions = [
            'timeout' => $timeout,
        ];

        // Add Authorization header if API key available (required for premium models)
        if ($this->apiKey !== null) {
            $requestOptions['headers'] = [
                'Authorization' => 'Bearer '.$this->apiKey,
            ];
        }

        try {
            $response = $this->httpClient->request('GET', $url, $requestOptions);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw AiProviderException::httpError(self::PROVIDER_NAME, $statusCode, $response->getContent(false));
            }

            $content = $response->getContent();
            $contentType = $response->getHeaders()['content-type'][0] ?? 'image/png';

            // Normalize content type
            $mimeType = $this->normalizeMimeType($contentType);

            $this->logger?->info('[Pollinations] Image generated successfully', [
                'model' => $model,
                'size' => \strlen($content),
                'mime_type' => $mimeType,
            ]);

            return new ImageResult(
                bytes: $content,
                mimeType: $mimeType,
                provider: self::PROVIDER_NAME,
                width: $width,
                height: $height,
                model: $model,
                seed: $seed,
                prompt: $prompt,
            );
        } catch (ExceptionInterface $e) {
            $this->logger?->error('[Pollinations] Request failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'timeout')) {
                throw AiProviderException::timeout(self::PROVIDER_NAME, $timeout);
            }

            throw AiProviderException::connectionError(self::PROVIDER_NAME, $e->getMessage());
        }
    }

    /**
     * Encode prompt for URL (spaces as %20, handle special characters).
     *
     * Sanitizes problematic characters that trigger Cloudflare WAF:
     * - % sign: blocked when URL-encoded as %25 (looks like injection attack)
     */
    private function encodePrompt(string $prompt): string
    {
        // Replace multiple spaces with single space
        $prompt = preg_replace('/\s+/', ' ', trim($prompt));

        // Sanitize % sign to avoid Cloudflare WAF blocking %25
        $prompt = str_replace('%', ' percent', $prompt);

        // URL encode the entire prompt
        return rawurlencode($prompt);
    }

    /**
     * Normalize MIME type from response header.
     */
    private function normalizeMimeType(string $contentType): string
    {
        // Remove charset and other parameters
        $mimeType = explode(';', $contentType)[0];
        $mimeType = trim($mimeType);

        return match (true) {
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => 'image/jpeg',
            str_contains($mimeType, 'png') => 'image/png',
            str_contains($mimeType, 'webp') => 'image/webp',
            str_contains($mimeType, 'gif') => 'image/gif',
            default => 'image/png',
        };
    }
}
