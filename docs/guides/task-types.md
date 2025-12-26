# Task Types Guide

Learn how to configure and use Task Types to select the optimal AI model for each operation.

## Overview

Task Types allow you to specify which AI model to use for different operations in your application. Instead of using a single model for everything, you can optimize cost and quality by selecting models appropriate for each task:

| Task Type | Purpose | Recommended Model |
|-----------|---------|-------------------|
| `NEWS_CONTENT` | Article/content generation | `claude` (high quality) |
| `IMAGE_PROMPT` | Scene descriptions for images | `gemini-fast` (fast, cheap) |
| `IMAGE_GENERATION` | Actual image creation | `gptimage` (complex scenes) |

## Architecture

The system separates **model data** from **user configuration**:

```
ModelRegistryService          TaskConfigService
────────────────────         ─────────────────────
Model catalog                Your project config
(immutable)                  (customizable)

claude: 330/pollen    ───>   news_content:
gemini-fast: 12000/pollen      default: claude
gptimage: 160/pollen           allowed: [claude, gemini]
```

- **ModelRegistryService**: Contains immutable data from Pollinations (model names, costs)
- **TaskConfigService**: Applies your project's configuration (which models are allowed)

This separation allows:
- Bundle updates don't break your configuration
- Projects can restrict models based on budget
- Cost information stays accurate with provider data

## Configuration

Configure Task Types in your project's `xmon_ai_content.yaml`:

```yaml
xmon_ai_content:
    tasks:
        news_content:
            default_model: 'claude'
            allowed_models: ['claude', 'gemini', 'openai', 'gemini-fast', 'mistral']

        image_prompt:
            default_model: 'gemini-fast'
            allowed_models: ['openai-fast', 'gemini-fast', 'mistral']

        image_generation:
            default_model: 'gptimage'
            allowed_models: ['flux', 'gptimage', 'seedream', 'nanobanana', 'turbo']
```

### Configuration Fields

| Field | Description |
|-------|-------------|
| `default_model` | Model used when none is explicitly requested |
| `allowed_models` | Models that can be used for this task |

### Budget-Based Configurations

**Premium (Best Quality):**

```yaml
tasks:
    news_content:
        default_model: 'claude'        # ~$0.003/article
        allowed_models: ['claude', 'gemini']
    image_prompt:
        default_model: 'gemini-fast'   # ~$0.00008/prompt
        allowed_models: ['gemini-fast', 'openai-fast']
    image_generation:
        default_model: 'gptimage'      # ~$0.006/image
        allowed_models: ['gptimage', 'seedream', 'flux']
```

**Free (No Cost):**

```yaml
tasks:
    news_content:
        default_model: 'openai'        # Free tier
        allowed_models: ['openai', 'openai-fast', 'mistral']
    image_prompt:
        default_model: 'openai-fast'   # Free tier
        allowed_models: ['openai-fast', 'mistral']
    image_generation:
        default_model: 'flux'          # Free tier
        allowed_models: ['flux', 'turbo']
```

## Usage in Code

### Text Generation

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
        $result = $this->aiTextService->generateForTask(
            TaskType::NEWS_CONTENT,
            'You are a professional journalist.',
            "Write an article about: {$title}\n\nSummary: {$summary}",
        );

        return $result->getContent();
    }
}
```

### Image Prompt Generation

```php
use Xmon\AiContentBundle\Enum\TaskType;

public function generateImagePrompt(string $title, string $content): string
{
    $result = $this->aiTextService->generateForTask(
        TaskType::IMAGE_PROMPT,
        'Generate a scene description for image generation.',
        "Title: {$title}\nContent: {$content}",
    );

    return $result->getContent();
}
```

### Image Generation

```php
use Xmon\AiContentBundle\Enum\TaskType;
use Xmon\AiContentBundle\Service\AiImageService;

class ImageGenerator
{
    public function __construct(
        private readonly AiImageService $aiImageService,
    ) {}

    public function generateImage(string $prompt): ImageResult
    {
        return $this->aiImageService->generateForTask(
            $prompt,
            ['width' => 1280, 'height' => 720],
        );
    }
}
```

### Override Model at Runtime

You can override the default model for a specific call:

```php
// Use a specific model (must be in allowed_models)
$result = $this->aiTextService->generateForTask(
    TaskType::NEWS_CONTENT,
    $systemPrompt,
    $userMessage,
    ['model' => 'gemini'],  // Override default
);

// For images
$result = $this->aiImageService->generateForTask(
    $prompt,
    ['model' => 'seedream', 'width' => 1920, 'height' => 1080],
);
```

## Getting Model Information

### List Allowed Models for a Task

```php
// Get allowed models with full info (ModelInfo objects)
$models = $this->aiTextService->getAllowedModelsForTask(TaskType::NEWS_CONTENT);

foreach ($models as $key => $modelInfo) {
    echo $key;                         // 'claude'
    echo $modelInfo->name;             // 'Claude Sonnet 4.5'
    echo $modelInfo->responsesPerPollen; // 330
    echo $modelInfo->getFormattedCost(); // '~330 per pollen'
}
```

### For UI Selects

```php
// Get models formatted for HTML selects
$options = $this->aiImageService->getAllowedModelsForSelect();
// ['gptimage' => 'OpenAI Image 1 Mini (~160 per pollen)', ...]
```

### Get Default Model

```php
$defaultModel = $this->aiTextService->getDefaultModelForTask(TaskType::IMAGE_PROMPT);
// 'gemini-fast'
```

## Available Models

### Text Models

| Key | Name | ~Responses/Pollen | Best For |
|-----|------|-------------------|----------|
| `claude` | Claude Sonnet 4.5 | 330 | High-quality content |
| `gemini` | Gemini 3 Flash | 1,600 | General content |
| `openai` | GPT-5 Mini | 8,000 | General purpose |
| `gemini-fast` | Gemini 2.5 Flash Lite | 12,000 | Fast operations |
| `openai-fast` | GPT-5 Nano | 11,000 | Quick tasks |
| `mistral` | Mistral Small | 13,000 | Fallback |

### Image Models

| Key | Name | ~Images/Pollen | Best For |
|-----|------|----------------|----------|
| `gptimage` | OpenAI Image 1 Mini | 160 | Complex scenes (aikido, hakamas) |
| `seedream` | ByteDance ARK 2K | 35 | High quality |
| `nanobanana` | Gemini Image | 25 | Reference-based |
| `flux` | Flux (free) | 8,300 | Good default |
| `turbo` | Turbo (free) | 3,300 | Fast previews |

## Cost Estimation

With premium defaults (1 pollen = $1 USD):

| Operation | Model | Cost |
|-----------|-------|------|
| Article content | claude | ~$0.003 |
| Image prompt | gemini-fast | ~$0.00008 |
| Image generation | gptimage | ~$0.006 |
| **Complete article with image** | | **~$0.01** |

> **100 complete articles (with images) = ~$1 USD**

## Best Practices

### 1. Match Model to Task

- **NEWS_CONTENT**: Use quality models (claude, gemini) - content matters
- **IMAGE_PROMPT**: Use fast models (gemini-fast) - just generating descriptions
- **IMAGE_GENERATION**: Match to your content type

### 2. Restrict Allowed Models by Budget

Only include models you're willing to pay for:

```yaml
# Tight budget - only free models
image_generation:
    default_model: 'flux'
    allowed_models: ['flux', 'turbo']  # No premium models

# Flexible budget - include premium
image_generation:
    default_model: 'gptimage'
    allowed_models: ['flux', 'gptimage', 'seedream']  # Free + premium
```

### 3. Use Default Model Wisely

The default model is used when no model is specified. Choose based on your typical use case:

```yaml
# If most content is premium quality
news_content:
    default_model: 'claude'

# If testing frequently
image_generation:
    default_model: 'flux'  # Free for testing
```

## Error Handling

If a requested model is not in `allowed_models`, an exception is thrown:

```php
try {
    $result = $this->aiTextService->generateForTask(
        TaskType::NEWS_CONTENT,
        $system,
        $user,
        ['model' => 'gpt-4-turbo'],  // Not in allowed_models
    );
} catch (AiProviderException $e) {
    // "Model 'gpt-4-turbo' is not allowed for task 'news_content'"
}
```

## Related

- [Configuration Reference](../reference/configuration.md) - Full YAML options
- [Providers Reference](../reference/providers.md) - Model details and costs
- [Text Generation](text-generation.md) - Text generation guide
- [Image Generation](image-generation.md) - Image generation guide
