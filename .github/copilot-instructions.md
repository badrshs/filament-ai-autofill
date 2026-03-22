# Filament AI Autofill — Workspace Instructions

## Project

Laravel/Filament package (`badrsh/filament-ai-autofill`) providing AI-powered translation for form fields. Supports Filament v3/v4/v5, Laravel 10–13, PHP 8.1+.


## Testing Workflow The "done" Command Loop ( SUPER CRITICAL )

Before completing ANY task, you MUST:

1. Run `php .\artisan done` command
2. Wait for user to test and potentially modify the command
3. If user modifies the command, address their feedback
4. Run `php .\artisan done` again
5. Repeat until the command runs successfully without user modifications

**This creates an iterative feedback loop that only ends when the user is satisfied with the implementation.**

Example workflow:
```powershell
# After implementing feature
php .\artisan done

# User might change it to:
# "Actually, the validation is wrong, fix the email validation"

# Fix the issue, then run again:
php .\artisan done

# Continue until user doesn't modify the command
```

## Build & Test

```
composer install
vendor/bin/pest            # run all tests
vendor/bin/pest --ci       # CI mode (used in GitHub Actions)
```

No composer scripts — invoke Pest directly. CI matrix covers PHP 8.1–8.4.

## Architecture

```
src/
├── FilamentAiAutofillPlugin.php     # Filament Plugin (panel-level config)
├── FilamentAiAutofillServiceProvider.php  # Spatie package service provider
├── Actions/                         # Filament suffix/header actions
│   ├── TranslateFieldAction.php     # Inline sparkle icon per field
│   └── TranslateBatchAction.php     # Batch translate all fields
├── Concerns/
│   └── HasTranslatableFields.php    # Trait for localized tab forms
├── Contracts/
│   └── Translator.php               # Interface all translators implement
├── Enums/
│   └── FieldNamingStrategy.php      # Suffix | DotNotation | AutoDetect
├── Support/
│   ├── FieldMapper.php              # Builds source→target field mappings
│   ├── FilamentCompat.php           # Filament version compatibility layer
│   └── TranslationConfig.php        # Immutable config value object
└── Translators/
    ├── LaravelAiTranslator.php      # Laravel AI SDK (multi-provider, default)
    ├── OpenAiTranslator.php         # Direct OpenAI HTTP calls
    └── NullTranslator.php           # No-op for testing
```

**Key patterns:**
- **Translator interface** — single `translate(array $values, string $sourceLocale, array $targetLocales): array` method
- **Fluent builders** — all config methods return `static` for chaining
- **Config priority** — action-level > plugin-level > config file > hardcoded defaults
- **Field mapping priority** — explicit `targetMapping()` > schema callback > auto-detection
- **Lazy container binding** — Translator resolved at request time via `config('filament-ai-autofill.translator')`

## Conventions

- **Namespace**: `Badrsh\FilamentAiAutofill`
- **PHPDoc** on all public methods with `@param`/`@return` tags
- **PHP 8.1 features**: named args, match expressions, union types, null coalescing
- **Tests**: Pest syntax (`test()` + `expect()` fluent assertions), fixtures in `tests/Fixtures/`
- **i18n**: translation files in `resources/lang/{en,ar}/`
- **Filament compat**: version-specific code goes in `Support/FilamentCompat.php`

## Gotchas

- `FilamentCompat.php` is autoloaded via `composer.json` `"files"` — not PSR-4
- `LaravelAiTranslator` requires `laravel/ai` (suggested, not required)
- `TranslationConfig` is immutable once created
- Release workflow: `release.ps1` (PowerShell) — auto-bumps semver, tags, pushes
