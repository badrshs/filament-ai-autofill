# Filament Translate Field

AI-powered field translation for [Filament](https://filamentphp.com) forms. Translate content between languages using OpenAI, DeepL, or any custom translator.

![Filament v3](https://img.shields.io/badge/Filament-v3-blue)
![Laravel](https://img.shields.io/badge/Laravel-10%2F11%2F12-red)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple)
![License](https://img.shields.io/badge/license-MIT-green)

## Features

- **Per-field inline action** — sparkle icon on any field to translate just that field
- **Batch action** — single button translates all source fields at once (one API call)
- **Translatable tabs helper** — build multi-locale tabbed forms with one method
- **Flexible field mapping** — supports suffix (`title_en`), dot notation (`title.en`), and auto-detection
- **Extensible translators** — ships with OpenAI, easily plug in DeepL or custom drivers
- **Overwrite protection** — optional confirmation before overwriting existing translations
- **Zero coupling** — works with any Filament form, any resource, any model

## Installation

```bash
composer require molham/filament-translate-field
```

Publish the config file:

```bash
php artisan vendor:publish --tag="filament-translate-field-config"
```

Set your OpenAI API key in `.env`:

```env
OPENAI_API_KEY=sk-your-key-here
```

### Optional: Register the Plugin

Add the plugin to your Filament panel for panel-level configuration:

```php
use Molham\FilamentTranslateField\FilamentTranslateFieldPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentTranslateFieldPlugin::make()
                ->sourceLocale('ar')
                ->targetLocales(['en', 'fr']),
        ]);
}
```

## Quick Start

### Option 1: Translatable Tabs (Recommended)

The fastest way to add translation to any resource:

```php
use Molham\FilamentTranslateField\Concerns\HasTranslatableFields;

class CampaignResource extends Resource
{
    use HasTranslatableFields;

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::translatableTabs(
                fn (string $locale, string $suffix) => [
                    Forms\Components\TextInput::make("title{$suffix}")
                        ->required($locale === 'ar'),
                    Forms\Components\Textarea::make("description{$suffix}"),
                    Forms\Components\RichEditor::make("details{$suffix}"),
                ],
            ),
        ]);
    }
}
```

This creates tabs (AR, EN, etc.) with a **"Auto-translate All"** button on the source tab.

### Option 2: Inline Per-Field Action

Add a sparkle icon to any individual field:

```php
use Molham\FilamentTranslateField\Actions\TranslateFieldAction;

Forms\Components\TextInput::make('title')
    ->suffixAction(
        TranslateFieldAction::make()
            ->targetFields(['en' => 'title_en', 'fr' => 'title_fr'])
    ),
```

### Option 3: Batch Action Button

Place a translate-all button anywhere in your form:

```php
use Molham\FilamentTranslateField\Actions\TranslateBatchAction;

Forms\Components\Actions::make([
    TranslateBatchAction::make()
        ->sourceFields(['title', 'description'])
        ->targetMapping([
            'title' => ['en' => 'title_en'],
            'description' => ['en' => 'description_en'],
        ]),
]),
```

## Configuration

```php
// config/filament-translate-field.php

return [
    // The translator driver class
    'translator' => \Molham\FilamentTranslateField\Translators\OpenAiTranslator::class,

    // Default source language
    'source_locale' => 'ar',

    // Default target languages
    'target_locales' => ['en'],

    // Field naming: 'suffix', 'dot', or 'auto'
    'field_naming' => 'auto',

    // Show confirmation before overwriting non-empty fields
    'confirm_overwrite' => true,

    // OpenAI settings
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => 'gpt-4o-mini',
        'base_url' => 'https://api.openai.com/v1',
    ],
];
```

## Field Naming Strategies

| Strategy | Source Field | Target Field (en) | Target Field (fr) |
|----------|------------|-------------------|-------------------|
| `suffix` | `title` | `title_en` | `title_fr` |
| `dot` | `title.ar` | `title.en` | `title.fr` |
| `auto` | Detects automatically based on your field names |

## Custom Translators

Implement the `Translator` contract:

```php
use Molham\FilamentTranslateField\Contracts\Translator;

class DeepLTranslator implements Translator
{
    public function translate(array $values, string $sourceLocale, array $targetLocales): array
    {
        // $values = ['title' => 'مرحبا', 'description' => 'نص طويل']
        // Return: ['title' => ['en' => 'Hello'], 'description' => ['en' => 'Long text']]

        $results = [];

        foreach ($values as $field => $text) {
            foreach ($targetLocales as $locale) {
                $results[$field][$locale] = $this->callDeepLApi($text, $sourceLocale, $locale);
            }
        }

        return $results;
    }
}
```

Register it in your config:

```php
'translator' => \App\Translators\DeepLTranslator::class,
```

Or override per-action:

```php
TranslateBatchAction::make()
    ->translator(\App\Translators\DeepLTranslator::class)
```

## API Reference

### TranslateFieldAction

Inline suffix action for a single field.

```php
TranslateFieldAction::make()
    ->targetFields(['en' => 'title_en'])  // Required: target field mapping
    ->sourceLocale('ar')                   // Override source locale
    ->targetLocales(['en', 'fr'])          // Override target locales
    ->translator(MyTranslator::class)      // Override translator
    ->confirmOverwrite(false)              // Skip overwrite confirmation
```

### TranslateBatchAction

Batch action for translating multiple fields at once.

```php
TranslateBatchAction::make()
    ->sourceFields(['title', 'description'])           // Explicit source fields
    ->targetMapping(['title' => ['en' => 'title_en']]) // Explicit mapping
    ->schemaCallback(fn ($locale, $suffix) => [...])   // Auto-discover from schema
    ->sourceLocale('ar')                                // Override source locale
    ->targetLocales(['en'])                             // Override target locales
    ->translator(MyTranslator::class)                   // Override translator
    ->fieldNaming('suffix')                             // Override naming strategy
    ->confirmOverwrite(false)                           // Skip overwrite confirmation
```

### HasTranslatableFields Trait

```php
static::translatableTabs(
    schemaCallback: fn (string $locale, string $suffix) => [...],
    label: 'Translations',          // Tab group label
    withBatchAction: true,          // Add batch button to source tab
    withFieldActions: false,        // Add inline icons to source fields
    sourceLocale: 'ar',             // Override source locale
    targetLocales: ['en', 'fr'],    // Override targets
    locales: ['ar', 'en', 'fr'],    // Override full locale list
)
```

### FieldMapper

Utility for building field mappings programmatically:

```php
use Molham\FilamentTranslateField\Support\FieldMapper;
use Molham\FilamentTranslateField\Enums\FieldNamingStrategy;

$config = FieldMapper::forFields(
    sourceFields: ['title', 'description'],
    sourceLocale: 'ar',
    targetLocales: ['en', 'fr'],
    strategy: FieldNamingStrategy::Suffix,
);

// $config->fieldMap = [
//     'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
//     'description' => ['en' => 'description_en', 'fr' => 'description_fr'],
// ]
```

## Translations

The package ships with English and Arabic translations. Publish to customize:

```bash
php artisan vendor:publish --tag="filament-translate-field-translations"
```

## Testing

```bash
composer test
```

For testing without API calls, use the `NullTranslator`:

```php
// In your test setup
config(['filament-translate-field.translator' => NullTranslator::class]);
```

## License

MIT License. See [LICENSE](LICENSE) for details.
