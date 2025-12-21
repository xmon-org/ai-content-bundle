<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Text;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

class OpenRouterTextProvider implements TextProviderInterface
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-exp:free';

    /**
     * Default fallback models (free tier).
     * Updated list: https://openrouter.ai/models?pricing=free.
     */
    private const DEFAULT_FALLBACK_MODELS = [
        'meta-llama/llama-3.3-70b-instruct:free',
        'qwen/qwen3-235b-a22b:free',
        'mistralai/mistral-small-3.1-24b-instruct:free',
        'google/gemma-3-27b-it:free',
    ];

    private string $model;
    private array $fallbackModels;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $apiKey = null,
        ?string $model = null,
        array $fallbackModels = [],
        private readonly int $timeout = 90,
        private readonly int $priority = 50,
        private readonly string $referer = 'https://aikido-nogara.com',
        private readonly string $title = 'AI Content Bundle',
    ) {
        $this->model = $model ?? self::DEFAULT_MODEL;
        $this->fallbackModels = !empty($fallbackModels) ? $fallbackModels : self::DEFAULT_FALLBACK_MODELS;
    }

    public function getName(): string
    {
        return 'openrouter';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey)
            && $this->apiKey !== 'sk-or-v1-xxx';
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
    {
        if (!$this->isAvailable()) {
            throw new AiProviderException('OpenRouter API key not configured', $this->getName());
        }

        $maxTokens = $options['max_tokens'] ?? 1500;
        $temperature = $options['temperature'] ?? 0.7;
        $specificModel = $options['model'] ?? null;

        // If a specific model is requested, only try that one
        // Otherwise, try primary model first, then fallbacks
        $modelsToTry = $specificModel
            ? [$specificModel]
            : array_merge([$this->model], $this->fallbackModels);

        $lastError = null;

        foreach ($modelsToTry as $model) {
            try {
                $result = $this->callWithModel($model, $systemPrompt, $userMessage, $maxTokens, $temperature);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Exception $e) {
                $lastError = $e;
                $this->logger?->warning('OpenRouter: Model failed', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        throw new AiProviderException(message: 'All OpenRouter models failed'.($lastError ? ': '.$lastError->getMessage() : ''), provider: $this->getName(), previous: $lastError);
    }

    private function callWithModel(
        string $model,
        string $systemPrompt,
        string $userMessage,
        int $maxTokens,
        float $temperature,
    ): ?TextResult {
        $this->logger?->debug('OpenRouter: Request', [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'system_prompt' => mb_substr($systemPrompt, 0, 500).(mb_strlen($systemPrompt) > 500 ? '...' : ''),
            'user_message' => mb_substr($userMessage, 0, 300).(mb_strlen($userMessage) > 300 ? '...' : ''),
        ]);

        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => $this->referer,
                'X-Title' => $this->title,
            ],
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ],
            'timeout' => $this->timeout,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $this->logger?->warning('OpenRouter: Non-200 response', [
                'model' => $model,
                'status' => $statusCode,
            ]);

            return null;
        }

        $data = $response->toArray();

        if (!isset($data['choices'][0]['message']['content'])) {
            $this->logger?->warning('OpenRouter: Response missing content', ['model' => $model]);

            return null;
        }

        $text = trim($data['choices'][0]['message']['content']);

        if (empty($text)) {
            $this->logger?->warning('OpenRouter: Empty response', ['model' => $model]);

            return null;
        }

        // OpenRouter provides token usage in the response
        $usage = $data['usage'] ?? [];

        $this->logger?->info('OpenRouter: Response OK', [
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

    /**
     * Get the primary model.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the list of fallback models.
     */
    public function getFallbackModels(): array
    {
        return $this->fallbackModels;
    }
}
