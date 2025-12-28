# Contributing to xmon-org/ai-content-bundle

Thanks for your interest in contributing!

## Environment Setup

```bash
# Clone the repository
git clone https://github.com/xmon-org/ai-content-bundle.git
cd ai-content-bundle

# Install dependencies
composer install

# Verify everything works
composer check
```

## Useful Commands

```bash
# Run tests
composer test

# Static analysis with PHPStan
composer phpstan

# Check code style
composer cs-check

# Fix code style automatically
composer cs-fix

# Run all verifications
composer check

# Generate PHPStan baseline (if there are legacy errors)
composer phpstan:baseline
```

## Git Hooks

Git hooks are configured automatically on `composer install`.

### Pre-commit Hook

Runs automatically before each commit:

1. **PHP-CS-Fixer**: Auto-formats staged PHP files
2. **PHPStan**: Static analysis on modified files in `src/` and `tests/`

If PHPStan finds errors, the commit is rejected.

### Bypassing Hooks (Not Recommended)

```bash
git commit --no-verify -m "message"
```

Only use in emergencies. Hooks prevent quality issues.

### Manual Hook Setup

If hooks are not working:

```bash
composer setup-hooks
```

## Commit Convention

This project uses [Conventional Commits](https://www.conventionalcommits.org/) for automated versioning and changelog generation.

### Format

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Allowed Types

| Type       | Description                              | Release   |
|------------|------------------------------------------|-----------|
| `feat`     | New feature                              | MINOR     |
| `fix`      | Bug fix                                  | PATCH     |
| `docs`     | Documentation only                       | -         |
| `style`    | Formatting (spaces, commas, etc)         | -         |
| `refactor` | Refactoring without functional change    | PATCH     |
| `perf`     | Performance improvement                  | PATCH     |
| `test`     | Add or fix tests                         | -         |
| `ci`       | CI/CD changes                            | -         |
| `chore`    | Maintenance                              | -         |

### Suggested Scopes

- `text` - Text generation (AiTextService, PollinationsTextProvider)
- `image` - Image generation (AiImageService, PollinationsImageProvider)
- `config` - Bundle YAML configuration
- `sonata` - Sonata Admin integration
- `tasks` - TaskTypes system and models per task
- `models` - Model registry and costs
- `prompts` - Prompt templates
- `styles` - Styles, presets and StyleProviders

### Examples

```bash
# New feature
git commit -m "feat(text): add streaming support for text generation"

# Bug fix
git commit -m "fix(image): resolve timeout with large prompts"

# Breaking change
git commit -m "feat(config)!: change YAML structure for image settings

BREAKING CHANGE: image.providers.pollinations is now image (flat structure)"

# Documentation
git commit -m "docs: add image generation examples"

# Refactoring
git commit -m "refactor(text): simplify fallback logic"
```

## Pull Request Process

1. **Create a branch** from `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feat/my-new-feature
   ```

2. **Make your changes** following the conventions

3. **Ensure verifications pass**:
   ```bash
   composer check
   ```

4. **Commit** using conventional commits

5. **Push** and create a PR to `develop`:
   ```bash
   git push -u origin feat/my-new-feature
   ```

## Project Structure

```
src/
├── DependencyInjection/    # Bundle configuration
├── Service/                # Main services
│   ├── AiTextService.php   # Text orchestrator
│   ├── AiImageService.php  # Image orchestrator
│   ├── TaskConfigService.php    # Models per TaskType
│   └── ModelRegistryService.php # Model catalog
├── Provider/
│   ├── Image/
│   │   └── PollinationsImageProvider.php  # Pollinations API (image)
│   ├── Text/
│   │   └── PollinationsTextProvider.php   # Pollinations API (text)
│   └── Style/
│       └── YamlStyleProvider.php          # Styles from YAML
├── Model/                  # DTOs (ImageResult, TextResult, ModelInfo)
├── Enum/                   # TaskType, ModelTier
└── XmonAiContentBundle.php # Main bundle class

tests/
└── ...                     # Unit and functional tests

docs/
├── installation.md
├── guides/                 # Usage guides
└── reference/              # Technical documentation
```

## Architecture

The bundle uses **Pollinations.ai** as the only AI provider. Pollinations provides unified access to multiple models (Claude, GPT, Gemini, Flux, etc.), so there's no need to implement multiple providers.

**Extension points:**

- `AiStyleProviderInterface` - Configure image styles from database
- Prompt templates - Configurable via YAML
- TaskTypes - Configure different models per task type

## Questions?

Open an issue if you have questions or suggestions.
