<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Text;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

/**
 * Pollinations Text Provider.
 *
 * Uses the Pollinations text API: GET https://gen.pollinations.ai/text/{prompt}?params...
 * Works without API key (free tier with rate limits).
 * With API key: higher rate limits and priority access.
 */
class PollinationsTextProvider implements TextProviderInterface
{
    private const BASE_URL = 'https://gen.pollinations.ai/text/';
    private const PROVIDER_NAME = 'pollinations';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $apiKey = null,
        private readonly string $model = 'openai',
        private readonly array $fallbackModels = [],
        private readonly int $timeout = 60,
        private readonly int $priority = 10,
    ) {
    }

    public function getName(): string
    {
        return self::PROVIDER_NAME;
    }

    /**
     * Pollinations is always available (free, no API key).
     */
    public function isAvailable(): bool
    {
        return true;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
    {
        $specificModel = $options['model'] ?? null;

        // If a specific model is requested, only try that one
        // Otherwise, try primary model first, then fallbacks
        $modelsToTry = $specificModel
            ? [$specificModel]
            : array_merge([$this->model], $this->fallbackModels);

        $lastError = null;

        foreach ($modelsToTry as $model) {
            try {
                return $this->callWithModel($model, $systemPrompt, $userMessage);
            } catch (\Exception $e) {
                $lastError = $e;
                $this->logger?->warning('[Pollinations] Model failed, trying next', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        throw new AiProviderException(message: 'All Pollinations models failed'.($lastError ? ': '.$lastError->getMessage() : ''), provider: $this->getName(), previous: $lastError);
    }

    private function callWithModel(string $model, string $systemPrompt, string $userMessage): TextResult
    {
        // Build URL with encoded prompt (user message)
        $encodedPrompt = $this->encodePrompt($userMessage);
        $url = self::BASE_URL.$encodedPrompt;

        // Build query parameters
        // Pollinations text API: model, seed, system, json, temperature, stream, private
        $seed = random_int(1, 1000000);

        $params = [
            'model' => $model,
            'seed' => $seed,
            'json' => 'false',
            'stream' => 'false',
            'private' => 'false',
        ];

        // Add system prompt if provided
        if (!empty($systemPrompt)) {
            $params['system'] = $systemPrompt;
        }

        $url .= '?'.http_build_query($params);

        $this->logger?->debug('[Pollinations] Request', [
            'model' => $model,
            'has_api_key' => !empty($this->apiKey),
            'system_prompt_length' => \strlen($systemPrompt),
            'user_message_length' => \strlen($userMessage),
        ]);

        // Build request options
        $requestOptions = [
            'timeout' => $this->timeout,
        ];

        // Add Authorization header if API key available
        if (!empty($this->apiKey)) {
            $requestOptions['headers'] = [
                'Authorization' => 'Bearer '.$this->apiKey,
            ];
        }

        try {
            $response = $this->httpClient->request('GET', $url, $requestOptions);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $this->logger?->warning('[Pollinations] Non-200 response', [
                    'model' => $model,
                    'status' => $statusCode,
                ]);

                throw AiProviderException::httpError(self::PROVIDER_NAME, $statusCode, "Model {$model} returned HTTP {$statusCode}");
            }

            // Response is plain text, not JSON
            $text = trim($response->getContent());

            if (empty($text)) {
                $this->logger?->warning('[Pollinations] Empty response', ['model' => $model]);

                throw AiProviderException::invalidResponse(self::PROVIDER_NAME, "Model {$model}: Empty response");
            }

            $this->logger?->info('[Pollinations] Response OK', [
                'model' => $model,
                'response_length' => \strlen($text),
                'response_preview' => mb_substr($text, 0, 200).(mb_strlen($text) > 200 ? '...' : ''),
            ]);

            return new TextResult(
                text: $text,
                provider: self::PROVIDER_NAME,
                model: $model,
                promptTokens: null,
                completionTokens: null,
                finishReason: null,
            );
        } catch (ExceptionInterface $e) {
            $this->logger?->error('[Pollinations] Request failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'timeout')) {
                throw AiProviderException::timeout(self::PROVIDER_NAME, $this->timeout);
            }

            throw AiProviderException::connectionError(self::PROVIDER_NAME, $e->getMessage());
        }
    }

    /**
     * Encode prompt for URL (spaces as %20, handle special characters).
     */
    private function encodePrompt(string $prompt): string
    {
        // Replace multiple spaces with single space
        $prompt = preg_replace('/\s+/', ' ', trim($prompt));

        // URL encode the entire prompt
        return rawurlencode($prompt);
    }
}
