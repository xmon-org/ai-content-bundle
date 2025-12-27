# Fallback System

The bundle implements an automatic fallback and retry system for AI operations.

## How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│                    AiTextService / AiImageService               │
│                                                                 │
│  generate(prompt, options)                                      │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              PollinationsProvider                        │   │
│  │                                                          │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌────────┐  │   │
│  │  │ Main     │─▶│ Fallback │─▶│ Fallback │─▶│ Error  │  │   │
│  │  │ Model    │  │ Model 1  │  │ Model 2  │  │        │  │   │
│  │  │(gemini)  │  │(mistral) │  │(openai)  │  │        │  │   │
│  │  └──────────┘  └──────────┘  └──────────┘  └────────┘  │   │
│  │       │             │             │             │       │   │
│  │   Success?      Success?      Success?      Retry?     │   │
│  │   Yes──▶Result  Yes──▶Result  Yes──▶Result  Yes──▶Loop │   │
│  │   No ──▶Next    No ──▶Next    No ──▶Retry   No ──▶Fail │   │
│  └─────────────────────────────────────────────────────────┘   │
│                              │                                  │
│                              ▼                                  │
│                    Result or AiProviderException                │
└─────────────────────────────────────────────────────────────────┘
```

## Fallback Flow

The fallback system operates at two levels:

### 1. Model Fallback (within provider)

If the main model fails, the provider tries configured fallback models:

```yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
                model: 'gemini'           # Primary model
                fallback_models:          # Backup models
                    - 'mistral'
                    - 'openai'
```

Flow:
1. Try `gemini` model
2. If fails, try `mistral`
3. If fails, try `openai`
4. If all fail, enter retry logic

### 2. Retry Logic

After exhausting all models, the system retries the entire sequence:

```yaml
xmon_ai_content:
    text:
        defaults:
            retries: 2        # Number of retry attempts
            retry_delay: 3    # Seconds between retries
```

Full example flow with `retries: 2`:

```
Attempt 1:
  gemini → fails
  mistral → fails
  openai → fails

Wait 3 seconds

Attempt 2:
  gemini → fails
  mistral → fails
  openai → fails

Wait 3 seconds

Attempt 3:
  gemini → fails
  mistral → fails
  openai → fails

→ AiProviderException (all attempts exhausted)
```

## Error Handling

### Smart Error Classification

The system distinguishes between retryable and non-retryable errors:

| Error Type | Retried? | Example |
|------------|----------|---------|
| HTTP 5xx | Yes | Server error |
| Timeout | Yes | Network timeout |
| Connection error | Yes | DNS failure |
| HTTP 4xx | **No** | Bad request, authentication error |
| Invalid response | Yes | Malformed JSON |

**Why skip 4xx errors?** Client errors (authentication, bad request) won't succeed on retry. The system fails fast instead of wasting time.

### Exception Handling

```php
use Xmon\AiContentBundle\Exception\AiProviderException;

try {
    $result = $this->aiTextService->generate($systemPrompt, $userMessage);
} catch (AiProviderException $e) {
    // Get details about the failure
    $provider = $e->getProvider();        // 'pollinations'
    $statusCode = $e->getHttpStatusCode(); // 429, 500, etc.
    $message = $e->getMessage();

    // Handle specific cases
    if ($statusCode === 429) {
        // Rate limited - wait and retry later
    }
}
```

### Factory Methods for Exceptions

```php
// Specific error types
AiProviderException::httpError('pollinations', 500, 'Server error');
AiProviderException::timeout('pollinations', 120);
AiProviderException::connectionError('pollinations', 'DNS resolution failed');
AiProviderException::invalidResponse('pollinations', 'Missing content field');
```

## Provider Availability

A provider is considered available when:
- `enabled: true` in configuration
- `isAvailable()` returns `true`

For Pollinations:
- **Without API key**: Available for anonymous tier models (`openai`, `openai-fast`, `flux`, `turbo`)
- **With API key**: Available for all tier models

| Configuration | Available? | Models |
|---------------|------------|--------|
| `enabled: false` | No | None |
| `enabled: true`, no API key | Yes | Anonymous tier only |
| `enabled: true`, with API key | Yes | All tiers |

## Configuration Examples

### Conservative (more retries, longer waits)

```yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
                model: 'gemini'
                fallback_models: ['mistral', 'openai-fast', 'openai']
                timeout: 90
        defaults:
            retries: 3
            retry_delay: 5
```

### Fast-fail (minimal retries)

```yaml
xmon_ai_content:
    text:
        providers:
            pollinations:
                enabled: true
                model: 'openai-fast'
                fallback_models: []  # No fallback
                timeout: 30
        defaults:
            retries: 1
            retry_delay: 1
```

### Image Generation (longer timeouts)

```yaml
xmon_ai_content:
    image:
        providers:
            pollinations:
                enabled: true
                model: 'flux'
                timeout: 120      # Images take longer
        defaults:
            retries: 3
            retry_delay: 5
```

## Task Types Integration

With TaskTypes (v1.4.0+), the model is selected based on task type before entering the fallback system:

```php
// TaskConfigService resolves model first
$model = $taskConfigService->resolveModel(TaskType::NEWS_CONTENT, $requestedModel);

// Then AiTextService uses resolved model with fallback
$result = $aiTextService->generateForTask(TaskType::NEWS_CONTENT, $system, $user, [
    'model' => $model,
]);
```

See [Task Types Guide](../guides/task-types.md) for more details.

## Related

- [Providers Reference](providers.md) - Available models and tiers
- [Configuration Reference](configuration.md) - Full YAML options
- [Task Types Guide](../guides/task-types.md) - Model configuration per task
