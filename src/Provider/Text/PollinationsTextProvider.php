<?php

declare(strict_types=1);

namespace Xmon\AiContentBundle\Provider\Text;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

/**
 * Pollinations Text Provider - Always available, free, no API key required.
 * Used as the final fallback in the provider chain.
 */
class PollinationsTextProvider implements TextProviderInterface
{
    private const API_URL = 'https://text.pollinations.ai/openai';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
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
     * Pollinations is always available (free, no API key)
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
        $model = $options['model'] ?? $this->model;

        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $this->logger?->debug('Pollinations: Generating text', [
            'model' => $model,
            'system_length' => strlen($systemPrompt),
            'user_length' => strlen($userMessage),
        ]);

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                ],
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $body = $response->getContent(false);
                throw new AiProviderException(
                    "Pollinations API returned status {$statusCode}: " . substr($body, 0, 300),
                    $this->getName()
                );
            }

            $data = $response->toArray();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new AiProviderException(
                    'Pollinations response missing content',
                    $this->getName()
                );
            }

            $text = trim($data['choices'][0]['message']['content']);

            if (empty($text)) {
                throw new AiProviderException(
                    'Pollinations returned empty response',
                    $this->getName()
                );
            }

            $this->logger?->info('Pollinations: Text generated successfully', [
                'model' => $model,
                'response_length' => strlen($text),
            ]);

            // Pollinations may provide usage info
            $usage = $data['usage'] ?? [];

            return new TextResult(
                text: $text,
                provider: $this->getName(),
                model: $model,
                promptTokens: $usage['prompt_tokens'] ?? null,
                completionTokens: $usage['completion_tokens'] ?? null,
                finishReason: $data['choices'][0]['finish_reason'] ?? null,
            );
        } catch (AiProviderException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AiProviderException(
                message: 'Pollinations request failed: ' . $e->getMessage(),
                provider: $this->getName(),
                previous: $e
            );
        }
    }
}
