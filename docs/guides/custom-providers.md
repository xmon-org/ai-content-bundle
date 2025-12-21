# Custom Providers Guide

You can add your own text providers by implementing `TextProviderInterface`. The bundle automatically detects them thanks to `#[AutoconfigureTag]`.

## Create a Custom Provider

```php
// src/Provider/AnthropicTextProvider.php
namespace App\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\AiContentBundle\Model\TextResult;
use Xmon\AiContentBundle\Provider\TextProviderInterface;

class AnthropicTextProvider implements TextProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
        private readonly int $priority = 80,
    ) {}

    public function getName(): string
    {
        return 'anthropic';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getPriority(): int
    {
        return $this->priority; // Between Gemini (100) and OpenRouter (50)
    }

    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult
    {
        // Your implementation...
        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $options['model'] ?? 'claude-3-haiku-20240307',
                'max_tokens' => $options['max_tokens'] ?? 1024,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ],
        ]);

        $data = $response->toArray();

        return new TextResult(
            text: $data['content'][0]['text'],
            provider: $this->getName(),
            model: $data['model'],
            promptTokens: $data['usage']['input_tokens'] ?? null,
            completionTokens: $data['usage']['output_tokens'] ?? null,
            finishReason: $data['stop_reason'] ?? null,
        );
    }
}
```

## Register the Provider

With `autoconfigure: true` (default in Symfony), the provider is registered automatically. You only need to configure the arguments:

```yaml
# config/services.yaml
App\Provider\AnthropicTextProvider:
    arguments:
        $apiKey: '%env(XMON_AI_ANTHROPIC_API_KEY)%'
        $priority: 80
    tags:
        - { name: 'xmon_ai_content.text_provider', priority: 80 }
```

The provider will automatically appear in the fallback chain according to its priority.

## TextProviderInterface

```php
interface TextProviderInterface
{
    /**
     * Unique name for this provider
     */
    public function getName(): string;

    /**
     * Whether the provider is currently available (has API key, etc.)
     */
    public function isAvailable(): bool;

    /**
     * Priority for fallback ordering (higher = tried first)
     */
    public function getPriority(): int;

    /**
     * Generate text from prompts
     *
     * @param string $systemPrompt System instructions
     * @param string $userMessage User's message
     * @param array $options Additional options (model, temperature, etc.)
     * @return TextResult
     * @throws AiProviderException on failure
     */
    public function generate(string $systemPrompt, string $userMessage, array $options = []): TextResult;
}
```

## ImageProviderInterface

For image providers:

```php
interface ImageProviderInterface
{
    public function getName(): string;
    public function isAvailable(): bool;
    public function getPriority(): int;

    /**
     * @param string $prompt Image description
     * @param array $options width, height, model, seed, etc.
     * @return ImageResult
     */
    public function generate(string $prompt, array $options = []): ImageResult;
}
```

## Priority Guidelines

| Priority Range | Use Case |
|----------------|----------|
| 100+ | Primary providers (fastest, most reliable) |
| 50-99 | Secondary providers (good fallback) |
| 10-49 | Tertiary providers (last resort) |
| 1-9 | Free/unlimited providers (final fallback) |

Default bundle priorities:
- Gemini: 100
- OpenRouter: 50
- Pollinations: 10

## Verify Registration

```bash
# See registered providers
bin/console debug:container --tag=xmon_ai_content.text_provider

# See full configuration
bin/console xmon:ai:debug
```

## Related

- [Providers Reference](../reference/providers.md) - Available providers
- [Fallback System](../reference/fallback-system.md) - How fallback works
- [Architecture](../reference/architecture.md) - Bundle structure
