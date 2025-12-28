# Text Generation Guide

How to generate text content using the AI provider.

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
            systemPrompt: 'Eres un asistente que resume contenido. Responde en espanol.',
            userMessage: "Resume este texto: {$content}",
        );

        // $result is a TextResult with:
        // - getText(): the generated text
        // - getProvider(): 'pollinations'
        // - getModel(): model used (e.g., 'gemini', 'openai-fast')
        // - getPromptTokens(), getCompletionTokens()
        // - getFinishReason(): 'stop', 'length', etc.

        return $result->getText();
    }
}
```

## Generation Options

You can customize behavior per-request:

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage, [
    'model' => 'claude',              // Specific model (see Providers Reference)
    'use_fallback' => false,          // Don't try fallback models
    'timeout' => 120,                 // Custom timeout (seconds)
    'retries_per_model' => 1,         // Fewer retries
    'retry_delay' => 1,               // Shorter delay between retries
]);
```

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `model` | string | from config | Override the default model |
| `use_fallback` | bool | * | Whether to try fallback models on failure |
| `timeout` | int | from config | Request timeout in seconds |
| `retries_per_model` | int | from config | Retries before trying next model |
| `retry_delay` | int | from config | Seconds between retries |

\* `use_fallback` defaults to `true` if no model specified, `false` if model is specified.

### Fallback Behavior

| Scenario | `use_fallback` | Behavior |
|----------|----------------|----------|
| No model specified | `true` (default) | Uses config model + fallback_models |
| Model specified | `false` (default) | Only tries the specified model |
| Model + use_fallback: true | `true` | Specified model + fallback_models |

## TextResult Object

The `generate()` method returns a `TextResult` object:

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage);

$result->getText();            // The generated text
$result->getProvider();        // 'pollinations'
$result->getModel();           // Model used (e.g., 'gemini', 'openai-fast')
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

## Overriding Model in Task Types

Even when using Task Types, you can override the model per-request:

```php
// Use a specific model for this call (must be in allowed_models for the task)
$result = $this->aiTextService->generateForTask(
    TaskType::NEWS_CONTENT,
    $systemPrompt,
    $userMessage,
    ['model' => 'claude']  // Override default model
);
```

## Getting Available Models

```php
// Get models allowed for a task type
$models = $this->aiTextService->getAllowedModelsForTask(TaskType::NEWS_CONTENT);
// Returns: ['claude' => ModelInfo, 'gemini' => ModelInfo, ...]

// Get models formatted for UI select
$options = $this->aiTextService->getAllowedModelsForSelect(TaskType::NEWS_CONTENT);
// Returns: ['claude' => 'Claude Sonnet 4.5 (~65 resp/$)', 'gemini' => 'Gemini 3 Flash (~330 resp/$)']

// Get default model for a task
$default = $this->aiTextService->getDefaultModelForTask(TaskType::NEWS_CONTENT);
// Returns: 'gemini' (or whatever is configured)
```

## Error Handling

```php
use Xmon\AiContentBundle\Exception\AiProviderException;

try {
    $result = $this->aiTextService->generate($systemPrompt, $userMessage);
} catch (AiProviderException $e) {
    // Provider error (rate limit, timeout, all models failed, etc.)
    $provider = $e->getProvider();        // 'pollinations'
    $statusCode = $e->getHttpStatusCode(); // 429, 500, null (timeout), etc.
    $message = $e->getMessage();

    // Handle specific cases
    if ($statusCode === 429) {
        // Rate limited - wait and retry later
    }
}
```

## Checking Provider Status

```php
// Check if the text provider is configured and available
if ($this->aiTextService->isConfigured()) {
    // Ready to generate
}

// Get list of available providers
$providers = $this->aiTextService->getAvailableProviders();
// Returns: ['pollinations']
```

## Related

- [Task Types](task-types.md) - Configure models per task type
- [Prompt Templates](prompt-templates.md) - Configurable system/user prompts
- [Providers Reference](../reference/providers.md) - Available text models and costs
- [Fallback System](../reference/fallback-system.md) - How model fallback works
- [Configuration Reference](../reference/configuration.md) - Full YAML options
