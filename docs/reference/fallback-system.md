# Fallback System

The bundle implements automatic fallback between models when one fails.

> **Architecture (December 2025):** Fallback happens at the MODEL level, not the provider level. Pollinations is the sole provider, but if one model fails, the system tries alternative models configured in `fallback_models`.

## How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│                         AiTextService                            │
│                                                                  │
│  generate(prompt, options)                                       │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              PollinationsTextProvider                    │    │
│  │                                                          │    │
│  │  Model sequence: gemini → mistral → deepseek             │    │
│  │                                                          │    │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐               │    │
│  │  │ gemini   │──│ mistral  │──│ deepseek │               │    │
│  │  │ (retry 2)│  │ (retry 2)│  │ (retry 2)│               │    │
│  │  └──────────┘  └──────────┘  └──────────┘               │    │
│  │       │             │             │                      │    │
│  │   Fail? ──────▶ Fail? ──────▶ Fail? ──────▶ Exception   │    │
│  │   OK ──▶ Return   OK ──▶ Return   OK ──▶ Return         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│                              ▼                                   │
│                    Result or AiProviderException                 │
└─────────────────────────────────────────────────────────────────┘
```

## Configuration

```yaml
xmon_ai_content:
    text:
        model: 'gemini'                    # Primary model
        fallback_models: ['mistral', 'deepseek']  # Backup models
        retries_per_model: 2               # Retries per model before next
        retry_delay: 3                     # Seconds between retries
        timeout: 60

    image:
        model: 'flux'
        fallback_models: ['turbo']
        retries_per_model: 2
        retry_delay: 3
        timeout: 120
```

## Fallback Flow

When a model fails, the provider follows this sequence:

```
Attempt 1 - gemini:
  Request 1 → fails (5xx)
  Wait 3 seconds
  Request 2 → fails (timeout)

Move to next model - mistral:
  Request 1 → fails (5xx)
  Wait 3 seconds
  Request 2 → success! → Return result

(If all models fail after all retries → AiProviderException)
```

## Per-Request Override

You can override fallback behavior on each call:

```php
// Use specific model WITHOUT fallback
$result = $aiTextService->generate($system, $user, [
    'model' => 'claude',           // Use this model only
    'use_fallback' => false,       // Don't try fallback_models
]);

// Use specific model WITH fallback
$result = $aiTextService->generate($system, $user, [
    'model' => 'claude',           // Try this first
    'use_fallback' => true,        // Then try fallback_models
]);

// Override retry settings
$result = $aiTextService->generate($system, $user, [
    'retries_per_model' => 1,      // Fewer retries (faster failure)
    'retry_delay' => 1,            // Shorter delay
    'timeout' => 30,               // Custom timeout
]);
```

### Fallback Behavior Matrix

| Scenario | `use_fallback` Default | Behavior |
|----------|------------------------|----------|
| No model specified | `true` | Uses config `model` + `fallback_models` |
| Model specified | `false` | Only tries the specified model |
| Model + `use_fallback: true` | `true` | Specified model + `fallback_models` |
| Model + `use_fallback: false` | `false` | Only the specified model |

## Smart Error Classification

The system distinguishes between retryable and non-retryable errors:

| Error Type | Retried? | Reason |
|------------|----------|--------|
| HTTP 5xx | Yes | Server error, might recover |
| Timeout | Yes | Network issue, might succeed |
| Connection error | Yes | Temporary network issue |
| HTTP 4xx | **No** | Client error, won't succeed on retry |
| Invalid response | Yes | Might be temporary |

**Why skip 4xx errors?** Client errors (authentication, bad request, moderation_blocked) won't succeed on retry. The system fails fast and moves to the next model instead of wasting retries.

## Exception Handling

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

### Exception Factory Methods

```php
// Specific error types
AiProviderException::httpError('pollinations', 500, 'Server error');
AiProviderException::timeout('pollinations', 120);
AiProviderException::connectionError('pollinations', 'DNS resolution failed');
AiProviderException::invalidResponse('pollinations', 'Missing content field');
```

## Provider Availability

A provider is considered available when `isAvailable()` returns `true`.

For Pollinations:
- **Always available**: Works without API key for anonymous tier
- **With API key**: Unlocks premium models

| Configuration | Available? | Accessible Models |
|---------------|------------|-------------------|
| No API key | Yes | Anonymous tier (`openai`, `openai-fast`, `flux`, `turbo`) |
| With API key | Yes | All tiers (seed + flower models) |

## Configuration Examples

### Conservative (More Resilience)

```yaml
xmon_ai_content:
    text:
        model: 'gemini'
        fallback_models: ['mistral', 'openai-fast', 'openai']
        retries_per_model: 3
        retry_delay: 5
        timeout: 90
```

### Fast-Fail (Minimal Latency)

```yaml
xmon_ai_content:
    text:
        model: 'openai-fast'
        fallback_models: []  # No fallback
        retries_per_model: 1
        retry_delay: 1
        timeout: 30
```

### Image Generation (Longer Timeouts)

```yaml
xmon_ai_content:
    image:
        model: 'flux'
        fallback_models: ['turbo']
        retries_per_model: 3
        retry_delay: 5
        timeout: 180  # Images take longer
```

## Task Types Integration

With TaskTypes, the model is resolved based on task type before entering the fallback system:

```php
// TaskConfigService resolves model from task configuration
$result = $aiTextService->generateForTask(
    TaskType::NEWS_CONTENT,
    $system,
    $user,
    ['model' => 'claude']  // Can still override
);
```

The resolved model becomes the primary model, and `fallback_models` from config are used as backups.

See [Task Types Guide](../guides/task-types.md) for more details.

## Logging

The bundle logs fallback activity to the `ai` channel:

```
[info] [Pollinations] Trying model {"model":"gemini","attempt":1,"max_attempts":2}
[warning] [Pollinations] Attempt failed {"model":"gemini","attempt":1,"error":"timeout","http_status":null}
[info] [Pollinations] Trying model {"model":"gemini","attempt":2,"max_attempts":2}
[warning] [Pollinations] Attempt failed {"model":"gemini","attempt":2,"error":"500","http_status":500}
[info] [Pollinations] Trying model {"model":"mistral","attempt":1,"max_attempts":2}
[info] [Pollinations] OpenAI POST response OK {"model":"mistral","response_length":1234}
```

Configure logging in your project:

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['ai']
    handlers:
        ai:
            type: stream
            path: '%kernel.logs_dir%/ai.log'
            level: debug
            channels: ['ai']
```

## Related

- [Configuration Reference](configuration.md) - Full YAML options
- [Providers Reference](providers.md) - Available models and tiers
- [Task Types Guide](../guides/task-types.md) - Model configuration per task
