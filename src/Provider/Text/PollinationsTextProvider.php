<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Text;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

/**
 * Pollinations Text Provider.
 *
 * Works without API key (free tier with rate limits).
 * With API key: higher rate limits and priority access.
 */
class PollinationsTextProvider implements TextProviderInterface
{
    private const API_URL = 'https://text.pollinations.ai/openai';

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
        return 'pollinations';
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
                $result = $this->callWithModel($model, $systemPrompt, $userMessage);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Exception $e) {
                $lastError = $e;
                $this->logger?->warning('Pollinations: Model failed, trying next', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        throw new AiProviderException(message: 'All Pollinations models failed'.($lastError ? ': '.$lastError->getMessage() : ''), provider: $this->getName(), previous: $lastError);
    }

    private function callWithModel(string $model, string $systemPrompt, string $userMessage): ?TextResult
    {
        // Note: Pollinations azure-openai backend only supports temperature=1 (default)
        // so we don't send temperature parameter to avoid 400 errors

        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $this->logger?->debug('Pollinations: Request', [
            'model' => $model,
            'has_api_key' => !empty($this->apiKey),
            'api_key_prefix' => $this->apiKey ? substr($this->apiKey, 0, 8).'...' : 'null',
            'system_prompt' => mb_substr($systemPrompt, 0, 500).(mb_strlen($systemPrompt) > 500 ? '...' : ''),
            'user_message' => mb_substr($userMessage, 0, 300).(mb_strlen($userMessage) > 300 ? '...' : ''),
        ]);

        $headers = [
            'Content-Type' => 'application/json',
        ];

        // Add API key if configured (higher rate limits)
        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer '.$this->apiKey;
        }

        // Add random seed to avoid cached responses
        $seed = random_int(1, 1000000);

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => $headers,
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'seed' => $seed,
            ],
            'timeout' => $this->timeout,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $this->logger?->warning('Pollinations: Non-200 response', [
                'model' => $model,
                'status' => $statusCode,
            ]);

            return null;
        }

        $data = $response->toArray();

        if (!isset($data['choices'][0]['message']['content'])) {
            $this->logger?->warning('Pollinations: Missing content in response', [
                'model' => $model,
                'response' => json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT),
            ]);

            return null;
        }

        $text = trim($data['choices'][0]['message']['content']);

        if (empty($text)) {
            $this->logger?->warning('Pollinations: Empty response', ['model' => $model]);

            return null;
        }

        // Pollinations may provide usage info
        $usage = $data['usage'] ?? [];

        $this->logger?->info('Pollinations: Response OK', [
            'model' => $model,
            'tokens' => ($usage['prompt_tokens'] ?? '?').'+'.($usage['completion_tokens'] ?? '?'),
            'response' => mb_substr($text, 0, 200).(mb_strlen($text) > 200 ? '...' : ''),
        ]);

        return new TextResult(
            text: $text,
            provider: $this->getName(),
            model: $model,
            promptTokens: $usage['prompt_tokens'] ?? null,
            completionTokens: $usage['completion_tokens'] ?? null,
            finishReason: $data['choices'][0]['finish_reason'] ?? null,
        );
    }
}
