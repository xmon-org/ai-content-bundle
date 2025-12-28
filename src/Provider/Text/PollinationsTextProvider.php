<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Text;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;

/**
 * Pollinations Text Provider.
 *
 * Supports two endpoint modes:
 *
 * - 'simple': GET https://gen.pollinations.ai/text/{prompt}?params...
 *   - Prompt in URL (URL-encoded), system prompt in query param
 *   - Response: plain text
 *   - Pros: Simple, less overhead
 *   - Cons: URL length limit (~2000 chars), long system prompts may fail
 *
 * - 'openai': POST https://gen.pollinations.ai/v1/chat/completions
 *   - JSON body with messages array (OpenAI-compatible)
 *   - Response: JSON with token usage info
 *   - Pros: No length limit, native system prompt support, token tracking
 *   - Cons: More parsing overhead
 *
 * Works without API key (free tier with rate limits).
 * With API key: higher rate limits and priority access.
 */
class PollinationsTextProvider
{
    private const SIMPLE_BASE_URL = 'https://gen.pollinations.ai/text/';
    private const OPENAI_BASE_URL = 'https://gen.pollinations.ai/v1/chat/completions';
    private const PROVIDER_NAME = 'pollinations';

    public const MODE_SIMPLE = 'simple';
    public const MODE_OPENAI = 'openai';

    /**
     * @param array<string> $fallbackModels
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $apiKey = null,
        private readonly string $model = 'gemini',
        private readonly array $fallbackModels = [],
        private readonly int $retriesPerModel = 2,
        private readonly int $retryDelay = 3,
        private readonly int $timeout = 60,
        private readonly string $endpointMode = self::MODE_OPENAI,
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

    /**
     * Get the provider configuration for debugging.
     *
     * @return array{
     *     model: string,
     *     fallback_models: array<string>,
     *     retries_per_model: int,
     *     retry_delay: int,
     *     timeout: int,
     *     endpoint_mode: string,
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
            'endpoint_mode' => $this->endpointMode,
            'has_api_key' => !empty($this->apiKey),
        ];
    }

    /**
     * Generate text with fallback models and per-model retries.
     *
     * @param array{
     *     model?: string,
     *     use_fallback?: bool,
     *     timeout?: int,
     *     retries_per_model?: int,
     *     retry_delay?: int
     * } $options
     *
     * @throws AiProviderException When all models fail after retries
     */
    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
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
        $timeout = $options['timeout'] ?? $this->timeout;

        $errors = [];

        foreach ($modelsToTry as $model) {
            for ($attempt = 1; $attempt <= $retriesPerModel; ++$attempt) {
                try {
                    $this->logger?->info('[Pollinations] Trying model', [
                        'model' => $model,
                        'attempt' => $attempt,
                        'max_attempts' => $retriesPerModel,
                    ]);

                    return $this->callWithModel($model, $systemPrompt, $userMessage, $timeout);
                } catch (AiProviderException $e) {
                    $this->logger?->warning('[Pollinations] Attempt failed', [
                        'model' => $model,
                        'attempt' => $attempt,
                        'max_attempts' => $retriesPerModel,
                        'error' => $e->getMessage(),
                        'http_status' => $e->getHttpStatusCode(),
                    ]);

                    // Don't retry on client errors (4xx)
                    if ($e->getHttpStatusCode() !== null && $e->getHttpStatusCode() >= 400 && $e->getHttpStatusCode() < 500) {
                        $errors[$model] = $e->getMessage();
                        break; // Skip to next model
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

        throw new AiProviderException(message: \sprintf('All text models failed: %s', $errorDetails ?: 'No models available'), provider: self::PROVIDER_NAME);
    }

    private function callWithModel(string $model, string $systemPrompt, string $userMessage, int $timeout): TextResult
    {
        return match ($this->endpointMode) {
            self::MODE_SIMPLE => $this->callWithSimpleEndpoint($model, $systemPrompt, $userMessage, $timeout),
            self::MODE_OPENAI => $this->callWithOpenAIEndpoint($model, $systemPrompt, $userMessage, $timeout),
            default => throw new \InvalidArgumentException("Invalid endpoint mode: {$this->endpointMode}"),
        };
    }

    /**
     * Simple GET endpoint: GET /text/{prompt}?model=...&system=...
     *
     * - Prompt in URL (URL-encoded)
     * - System prompt in query param
     * - Response: plain text
     * - Limit: ~2000 chars URL length
     */
    private function callWithSimpleEndpoint(string $model, string $systemPrompt, string $userMessage, int $timeout): TextResult
    {
        $encodedPrompt = $this->encodePrompt($userMessage);
        $url = self::SIMPLE_BASE_URL.$encodedPrompt;

        $seed = random_int(1, 1000000);

        $params = [
            'model' => $model,
            'seed' => $seed,
            'json' => 'false',
            'stream' => 'false',
            'private' => 'false',
        ];

        if (!empty($systemPrompt)) {
            $params['system'] = $systemPrompt;
        }

        $url .= '?'.http_build_query($params);

        $this->logger?->debug('[Pollinations] Simple GET request', [
            'model' => $model,
            'endpoint' => 'simple',
            'url_length' => \strlen($url),
            'has_api_key' => !empty($this->apiKey),
        ]);

        $requestOptions = [
            'timeout' => $timeout,
        ];

        if (!empty($this->apiKey)) {
            $requestOptions['headers'] = [
                'Authorization' => 'Bearer '.$this->apiKey,
            ];
        }

        try {
            $response = $this->httpClient->request('GET', $url, $requestOptions);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw AiProviderException::httpError(self::PROVIDER_NAME, $statusCode, "Model {$model} returned HTTP {$statusCode}");
            }

            $text = trim($response->getContent());

            if (empty($text)) {
                throw AiProviderException::invalidResponse(self::PROVIDER_NAME, "Model {$model}: Empty response");
            }

            $this->logger?->info('[Pollinations] Simple GET response OK', [
                'model' => $model,
                'response_length' => \strlen($text),
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
            $this->logger?->error('[Pollinations] Simple GET request failed', [
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
     * OpenAI-compatible endpoint: POST /v1/chat/completions.
     *
     * - JSON body with messages array
     * - Response: JSON with token usage info
     * - No URL length limit
     * - Native system prompt support
     */
    private function callWithOpenAIEndpoint(string $model, string $systemPrompt, string $userMessage, int $timeout): TextResult
    {
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $seed = random_int(1, 1000000);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'seed' => $seed,
        ];

        $this->logger?->debug('[Pollinations] OpenAI POST request', [
            'model' => $model,
            'endpoint' => 'openai',
            'has_api_key' => !empty($this->apiKey),
            'system_prompt_length' => \strlen($systemPrompt),
            'user_message_length' => \strlen($userMessage),
        ]);

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        try {
            $response = $this->httpClient->request('POST', self::OPENAI_BASE_URL, [
                'headers' => $headers,
                'json' => $payload,
                'timeout' => $timeout,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $errorBody = $response->getContent(false);
                throw AiProviderException::httpError(self::PROVIDER_NAME, $statusCode, "Model {$model} returned HTTP {$statusCode}: {$errorBody}");
            }

            $data = $response->toArray();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw AiProviderException::invalidResponse(self::PROVIDER_NAME, "Model {$model}: Missing content in response");
            }

            $text = trim($data['choices'][0]['message']['content']);

            if (empty($text)) {
                throw AiProviderException::invalidResponse(self::PROVIDER_NAME, "Model {$model}: Empty response");
            }

            $usage = $data['usage'] ?? [];

            $this->logger?->info('[Pollinations] OpenAI POST response OK', [
                'model' => $model,
                'response_length' => \strlen($text),
                'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? null,
            ]);

            return new TextResult(
                text: $text,
                provider: self::PROVIDER_NAME,
                model: $model,
                promptTokens: $usage['prompt_tokens'] ?? null,
                completionTokens: $usage['completion_tokens'] ?? null,
                finishReason: $data['choices'][0]['finish_reason'] ?? null,
            );
        } catch (ExceptionInterface $e) {
            $this->logger?->error('[Pollinations] OpenAI POST request failed', [
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
     * Used only for simple endpoint.
     */
    private function encodePrompt(string $prompt): string
    {
        $prompt = preg_replace('/\s+/', ' ', trim($prompt));

        return rawurlencode($prompt);
    }
}
