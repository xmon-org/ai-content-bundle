# Fallback System

The bundle implements an automatic fallback system between providers.

## How It Works

```
┌─────────────────────────────────────────────────────────────┐
│                    AiTextService                            │
│                                                             │
│  generate(systemPrompt, userMessage)                        │
│         │                                                   │
│         ▼                                                   │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │   Gemini    │───▶│ OpenRouter  │───▶│Pollinations │     │
│  │ (priority   │    │ (priority   │    │ (priority   │     │
│  │   100)      │    │   50)       │    │   10)       │     │
│  └─────────────┘    └─────────────┘    └─────────────┘     │
│         │                 │                   │             │
│         ▼                 ▼                   ▼             │
│      Error?            Error?             Error?           │
│         │                 │                   │             │
│    No ──▶ TextResult  No──▶ TextResult  No ──▶ TextResult  │
│    Yes──▶ Next        Yes──▶ Next       Yes──▶ Exception   │
└─────────────────────────────────────────────────────────────┘
```

## Fallback Flow

1. The system sorts providers by priority (highest first)
2. Attempts the first available provider (`isAvailable() = true`)
3. If it fails, moves to the next one
4. If all fail, throws `AllProvidersFailedException`

## Provider Availability

A provider is considered available when:
- `enabled: true` in configuration
- `isAvailable()` returns `true` (typically: has API key)

| Situation | Registered? | Used? |
|-----------|-------------|-------|
| `enabled: false` | No | No |
| `enabled: true` + no API key | Yes | No (skipped) |
| `enabled: true` + with API key | Yes | Yes |
| Not in YAML | No | No |

## Internal Model Fallback

Some providers (OpenRouter, Pollinations) support internal fallback between models:

```yaml
openrouter:
    model: 'google/gemini-2.0-flash-exp:free'
    fallback_models:
        - 'meta-llama/llama-3.3-70b-instruct:free'
        - 'qwen/qwen3-235b-a22b:free'

pollinations:
    model: 'openai-fast'
    fallback_models:
        - 'openai'
```

If the main model fails, the provider tries fallback models before the service moves to the next provider.

> **Note:** Pollinations models have tier requirements. `openai` and `openai-fast` work without API key (anonymous tier). Models like `mistral`, `gemini`, `deepseek` require a `seed` tier API key. See [Providers Reference](providers.md) for details.

## Retry Logic

Each provider attempt includes retry logic:

```yaml
text:
    defaults:
        retries: 2        # Retry failed requests
        retry_delay: 3    # Seconds between retries
```

Full flow:
1. Try Gemini
   - Attempt 1 → fails
   - Wait 3 seconds
   - Attempt 2 → fails
   - Wait 3 seconds
   - Attempt 3 → fails
2. Move to OpenRouter
   - (same retry logic)
3. Move to Pollinations
   - (same retry logic)
4. If all fail → `AllProvidersFailedException`

## Error Handling

```php
use Xmon\AiContentBundle\Exception\AllProvidersFailedException;
use Xmon\AiContentBundle\Exception\AiProviderException;

try {
    $result = $this->aiTextService->generate($systemPrompt, $userMessage);
} catch (AllProvidersFailedException $e) {
    // All providers failed
    foreach ($e->getErrors() as $provider => $error) {
        // Log each provider's error
        $this->logger->error("Provider {$provider} failed: {$error}");
    }
} catch (AiProviderException $e) {
    // Single provider error (when using specific provider option)
}
```

## Force Specific Provider

You can bypass the fallback system:

```php
$result = $this->aiTextService->generate($systemPrompt, $userMessage, [
    'provider' => 'gemini',  // Only use Gemini, no fallback
]);
```

If the specified provider fails, it throws immediately without trying others.

## Related

- [Providers Reference](providers.md) - Available providers
- [Configuration Reference](configuration.md) - Full YAML options
