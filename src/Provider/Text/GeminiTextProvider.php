<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Text;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

class GeminiTextProvider implements TextProviderInterface
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $apiKey = null,
        private readonly string $model = 'gemini-2.0-flash-lite',
        private readonly array $fallbackModels = [],
        private readonly int $timeout = 30,
        private readonly int $priority = 100,
    ) {
    }

    public function getName(): string
    {
        return 'gemini';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey)
            && $this->apiKey !== 'your_gemini_api_key_here'
            && str_starts_with($this->apiKey, 'AIza');
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
    {
        if (!$this->isAvailable()) {
            throw new AiProviderException('Gemini API key not configured', $this->getName());
        }

        $model = $options['model'] ?? $this->model;
        $temperature = $options['temperature'] ?? 0.9;
        $maxTokens = $options['max_tokens'] ?? 200;

        $url = self::API_BASE."/models/{$model}:generateContent";

        $this->logger?->debug('Gemini: Request', [
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'system_prompt' => mb_substr($systemPrompt, 0, 500).(mb_strlen($systemPrompt) > 500 ? '...' : ''),
            'user_message' => mb_substr($userMessage, 0, 300).(mb_strlen($userMessage) > 300 ? '...' : ''),
        ]);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'query' => ['key' => $this->apiKey],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $systemPrompt."\n\n".$userMessage],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxTokens,
                    ],
                ],
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $body = $response->getContent(false);
                throw new AiProviderException("Gemini API returned status {$statusCode}: {$body}", $this->getName());
            }

            $data = $response->toArray();

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new AiProviderException('Gemini response missing text content', $this->getName());
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            $finishReason = $data['candidates'][0]['finishReason'] ?? null;

            // Gemini provides token counts in usageMetadata
            $promptTokens = $data['usageMetadata']['promptTokenCount'] ?? null;
            $completionTokens = $data['usageMetadata']['candidatesTokenCount'] ?? null;

            $this->logger?->info('Gemini: Response OK', [
                'model' => $model,
                'tokens' => ($promptTokens ?? '?').'+'.($completionTokens ?? '?'),
                'response' => mb_substr($text, 0, 200).(mb_strlen($text) > 200 ? '...' : ''),
            ]);

            return new TextResult(
                text: $text,
                provider: $this->getName(),
                model: $model,
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                finishReason: $finishReason,
            );
        } catch (AiProviderException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AiProviderException(message: 'Gemini request failed: '.$e->getMessage(), provider: $this->getName(), previous: $e);
        }
    }
}
