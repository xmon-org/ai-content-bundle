# Text Generation Guide

How to generate text content using the AI providers.

## Basic Usage

```php
use Xmon\AiContentBundle\Service\AiTextService;

class MyService
{
    public function __construct(
        private readonly AiTextService $aiTextService,
    ) {}

    public function summarize(string $content): string
    {
        $result = $this->aiTextService->generate(
            systemPrompt: 'Eres un asistente que resume contenido. Responde en español.',
            userMessage: "Resume este texto: {$content}",
        );

        // $result is a TextResult with:
        // - getText(): the generated text
        // - getProvider(): 'gemini', 'openrouter', 'pollinations'
        // - getModel(): model used (e.g., 'gemini-2.0-flash-lite')
        // - getPromptTokens(), getCompletionTokens()
        // - getFinishReason(): 'stop', 'length', etc.

        return $result->getText();
    }
}
```

## Generation Options

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage, [
    'model' => 'gemini-2.0-flash',  // Specific model
    'temperature' => 0.7,           // Creativity (0.0 - 1.0)
    'max_tokens' => 1500,           // Token limit
    'provider' => 'gemini',         // Force specific provider
]);
```

### Available Options

| Option | Type | Description |
|--------|------|-------------|
| `model` | string | Override the default model |
| `temperature` | float | Creativity level (0.0 = deterministic, 1.0 = creative) |
| `max_tokens` | int | Maximum tokens in response |
| `provider` | string | Force a specific provider |

## TextResult Object

The `generate()` method returns a `TextResult` object with the following methods:

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage);

$result->getText();            // The generated text
$result->getProvider();        // 'gemini', 'openrouter', 'pollinations'
$result->getModel();           // Model used
$result->getPromptTokens();    // Input tokens (if available)
$result->getCompletionTokens(); // Output tokens (if available)
$result->getFinishReason();    // 'stop', 'length', etc.
```

## Using Prompt Templates

For more structured prompt management, use the [PromptTemplateService](prompt-templates.md):

```php
use Xmon\AiContentBundle\Service\PromptTemplateService;
use Xmon\AiContentBundle\Service\AiTextService;

class MyService
{
    public function __construct(
        private readonly PromptTemplateService $promptTemplates,
        private readonly AiTextService $aiTextService,
    ) {}

    public function summarize(string $content): string
    {
        $prompts = $this->promptTemplates->render('summarizer', [
            'content' => $content,
        ]);

        return $this->aiTextService->generate(
            $prompts['system'],
            $prompts['user']
        )->getText();
    }
}
```

## Error Handling

```php
use Xmon\AiContentBundle\Exception\AiProviderException;
use Xmon\AiContentBundle\Exception\AllProvidersFailedException;

try {
    $result = $this->aiTextService->generate($systemPrompt, $userMessage);
} catch (AllProvidersFailedException $e) {
    // All providers failed
    $errors = $e->getErrors(); // Array of errors per provider
} catch (AiProviderException $e) {
    // Single provider error
}
```

## Related

- [Prompt Templates](prompt-templates.md) - Configurable system/user prompts
- [Providers Reference](../reference/providers.md) - Available text providers
- [Fallback System](../reference/fallback-system.md) - How automatic fallback works
