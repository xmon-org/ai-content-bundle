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
        // - getProvider(): 'pollinations'
        // - getModel(): model used (e.g., 'openai', 'gemini-fast')
        // - getPromptTokens(), getCompletionTokens()
        // - getFinishReason(): 'stop', 'length', etc.

        return $result->getText();
    }
}
```

## Generation Options

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage, [
    'model' => 'gemini-fast',       // Specific model (see Available Models)
    'temperature' => 0.7,           // Creativity (0.0 - 1.0)
    'max_tokens' => 1500,           // Token limit
]);
```

### Available Options

| Option | Type | Description |
|--------|------|-------------|
| `model` | string | Override the default model (see [Providers Reference](../reference/providers.md)) |
| `temperature` | float | Creativity level (0.0 = deterministic, 1.0 = creative) |
| `max_tokens` | int | Maximum tokens in response |

## TextResult Object

The `generate()` method returns a `TextResult` object with the following methods:

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage);

$result->getText();            // The generated text
$result->getProvider();        // 'pollinations'
$result->getModel();           // Model used (e.g., 'openai', 'gemini-fast')
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

## Using Task Types (Recommended)

For production use, the recommended approach is to use Task Types, which allow you to configure different models for different purposes:

```php
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Service\AiTextService;

class NewsContentGenerator
{
    public function __construct(
        private readonly AiTextService $aiTextService,
    ) {}

    public function generateContent(string $title, string $summary): string
    {
        // Uses the model configured for NEWS_CONTENT task
        $result = $this->aiTextService->generateForTask(
            TaskType::NEWS_CONTENT,
            'You are a professional journalist.',
            "Write an article about: {$title}\n\nSummary: {$summary}",
        );

        return $result->getText();
    }

    public function generateImagePrompt(string $title): string
    {
        // Uses the model configured for IMAGE_PROMPT task (typically faster/cheaper)
        $result = $this->aiTextService->generateForTask(
            TaskType::IMAGE_PROMPT,
            'Generate a scene description for image generation.',
            "Title: {$title}",
        );

        return $result->getText();
    }
}
```

See [Task Types Guide](task-types.md) for complete configuration and usage examples.

## Error Handling

```php
use Xmon\AiContentBundle\Exception\AiProviderException;

try {
    $result = $this->aiTextService->generate($systemPrompt, $userMessage);
} catch (AiProviderException $e) {
    // Provider error (rate limit, timeout, etc.)
    $provider = $e->getProvider();        // 'pollinations'
    $statusCode = $e->getHttpStatusCode(); // 429, 500, etc.
    $message = $e->getMessage();
}
```

## Related

- [Task Types](task-types.md) - Configure models per task type
- [Prompt Templates](prompt-templates.md) - Configurable system/user prompts
- [Providers Reference](../reference/providers.md) - Available text models and costs
- [Fallback System](../reference/fallback-system.md) - How automatic fallback works
