# Git Hooks

This directory contains Git hooks for the bundle.

## Automatic Setup

Hooks are configured automatically when running:

```bash
composer install
```

## Pre-commit Hook

Runs before each commit:
1. **PHP-CS-Fixer** - Auto-formats code
2. **PHPStan** - Static analysis on modified files

If errors are found, the commit is rejected.

## More Information

See [CONTRIBUTING.md](../CONTRIBUTING.md) for:
- All available commands
- Commit message conventions
- Development setup
