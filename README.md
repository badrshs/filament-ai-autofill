# Filament AI Translate

AI-powered auto-translation for [Filament](https://filamentphp.com) form fields. Click a button, and your content is translated from one language to many — using OpenAI or any custom AI translator.

![Filament v3/v4/v5](https://img.shields.io/badge/Filament-v3%20%7C%20v4%20%7C%20v5-blue)
![Laravel](https://img.shields.io/badge/Laravel-10%2F11%2F12-red)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple)
![License](https://img.shields.io/badge/license-MIT-green)

## What It Does

You have a Filament form with fields in Arabic (or any source language). You want the English (or other) fields filled in automatically. This package adds:

1. **A sparkle ✨ icon** on individual fields — click it to translate that one field
2. **An "Auto-translate All" button** — translates every source field in one API call
3. **A tab helper** — builds AR / EN / FR tabs with translation built in

All translations happen via OpenAI by default. You can plug in DeepL, Google Translate, or any custom translator.

## Requirements

| Dependency | Versions |
|------------|----------|
| PHP | 8.1+ |
| Laravel | 10, 11, 12 |
| Filament | 3.x, 4.x, 5.x |

## Installation

### Step 1: Install the package

```bash
composer require badrsh/filament-ai-translate
```

### Step 2: Publish the config

```bash
php artisan vendor:publish --tag="filament-ai-translate-config"
```

### Step 3: Add your OpenAI key to `.env`

```env
OPENAI_API_KEY=sk-your-key-here
```

You can also customize the model and base URL via environment variables:

```env
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
```

That's it. The package works out of the box with any Filament form.

### Optional: Panel Plugin

If you want to override source/target locales at the panel level (instead of config):

```php
use Badrsh\FilamentAiTranslate\FilamentAiTranslatePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentAiTranslatePlugin::make()
                ->sourceLocale('ar')
                ->targetLocales(['en', 'fr']),
        ]);
}
```

---

## Usage

There are **3 ways** to use this package. Pick whichever fits your form layout.

### Approach 1: Translatable Tabs (Recommended)

Creates tabs (AR, EN, FR...) with an auto-translate button on the source tab.

```php
use Badrsh\FilamentAiTranslate\Concerns\HasTranslatableFields;

class PostResource extends Resource
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
                    Forms\Components\RichEditor::make("body{$suffix}"),
                ],
            ),
        ]);
    }
}
```

**What happens:**
- An **AR** tab appears with your source fields + an "Auto-translate All" button
- An **EN** tab (and others) with the same fields suffixed for that locale
- Clicking the button translates all filled AR fields → EN in a single API call

**Options you can pass:**

```php
static::translatableTabs(
    schemaCallback: fn (string $locale, string $suffix) => [...],
    label: 'Translations',       // Tab group label
    withBatchAction: true,       // Show the "Auto-translate All" button
    withFieldActions: false,     // Show sparkle icon on each source field
    sourceLocale: 'ar',          // Override source locale
    targetLocales: ['en', 'fr'], // Override target locales
    locales: ['ar', 'en', 'fr'], // Override full locale list
);
```

### Approach 2: Inline Per-Field Action

Add a sparkle icon (✨) to any individual field. When clicked, it translates that single field.

```php
use Badrsh\FilamentAiTranslate\Actions\TranslateFieldAction;

Forms\Components\TextInput::make('title')
    ->suffixAction(
        TranslateFieldAction::make()
            ->targetFields(['en' => 'title_en', 'fr' => 'title_fr'])
    ),
```

**What happens:**
- A sparkle icon appears on the right side of the `title` field
- Clicking it sends the Arabic text to OpenAI and fills `title_en` and `title_fr`

**Options:**

```php
TranslateFieldAction::make()
    ->targetFields(['en' => 'title_en'])  // Required: where to put translations
    ->sourceLocale('ar')                   // Override source locale
    ->targetLocales(['en', 'fr'])          // Override target locales
    ->translator(MyTranslator::class)      // Use a custom translator
    ->confirmOverwrite(false)              // Don't warn before overwriting
```

### Approach 3: Batch Action Button

Place a standalone "translate all" button anywhere in your form.

```php
use Badrsh\FilamentAiTranslate\Actions\TranslateBatchAction;

Forms\Components\Actions::make([
    TranslateBatchAction::make()
        ->sourceFields(['title', 'description'])
        ->targetMapping([
            'title'       => ['en' => 'title_en'],
            'description' => ['en' => 'description_en'],
        ]),
]),
```

**What happens:**
- A button appears in your form
- Clicking it collects all source field values, makes **one** API call, and distributes the translations

**Options:**

```php
TranslateBatchAction::make()
    ->sourceFields(['title', 'description'])           // Which fields to translate
    ->targetMapping(['title' => ['en' => 'title_en']]) // Explicit field mapping
    ->schemaCallback(fn ($locale, $suffix) => [...])   // Auto-discover from schema
    ->sourceLocale('ar')                                // Override source locale
    ->targetLocales(['en'])                             // Override target locales
    ->translator(MyTranslator::class)                   // Use a custom translator
    ->fieldNaming('suffix')                             // Override naming strategy
    ->confirmOverwrite(false)                           // Don't warn before overwriting
```

---

## Configuration

After publishing, edit `config/filament-ai-translate.php`:

```php
return [
    // The translator class (must implement Badrsh\FilamentAiTranslate\Contracts\Translator)
    'translator' => \Badrsh\FilamentAiTranslate\Translators\OpenAiTranslator::class,

    // The language your content is written in
    'source_locale' => 'ar',

    // The languages to translate into
    'target_locales' => ['en'],

    // How your form fields are named (see "Field Naming" below)
    'field_naming' => 'auto',    // 'suffix', 'dot', or 'auto'

    // Ask before overwriting fields that already have content
    'confirm_overwrite' => true,

    // OpenAI settings (all configurable via .env)
    'openai' => [
        'key'      => env('OPENAI_API_KEY'),
        'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],
];
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OPENAI_API_KEY` | *(required)* | Your OpenAI API key |
| `OPENAI_MODEL` | `gpt-4o-mini` | The model to use for translations |
| `OPENAI_BASE_URL` | `https://api.openai.com/v1` | API base URL (change for proxies or compatible APIs) |

---

## Field Naming Strategies

The package needs to know how your source and target fields are named so it can map between them.

| Strategy | Source Field | Target Field (en) | Target Field (fr) | When to use |
|----------|-------------|-------------------|-------------------|-------------|
| `suffix` | `title` | `title_en` | `title_fr` | Flat column per locale |
| `dot` | `title.ar` | `title.en` | `title.fr` | JSON/translatable columns |
| `auto` | *(detects)* | *(detects)* | *(detects)* | Let the package figure it out |

Set it in your config:

```php
'field_naming' => 'suffix', // or 'dot' or 'auto'
```

---

## Custom Translators

Don't want OpenAI? Implement the `Translator` interface:

```php
use Badrsh\FilamentAiTranslate\Contracts\Translator;

class DeepLTranslator implements Translator
{
    public function translate(array $values, string $sourceLocale, array $targetLocales): array
    {
        // Input:
        //   $values = ['title' => 'مرحبا', 'description' => 'نص طويل']
        //   $sourceLocale = 'ar'
        //   $targetLocales = ['en', 'fr']
        //
        // Expected output:
        //   [
        //       'title'       => ['en' => 'Hello', 'fr' => 'Bonjour'],
        //       'description' => ['en' => 'Long text', 'fr' => 'Texte long'],
        //   ]

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

Then set it in your config:

```php
'translator' => \App\Translators\DeepLTranslator::class,
```

Or override per-action:

```php
TranslateBatchAction::make()
    ->translator(\App\Translators\DeepLTranslator::class)
```

For testing without API calls, use the built-in `NullTranslator`:

```php
config(['filament-ai-translate.translator' => \Badrsh\FilamentAiTranslate\Translators\NullTranslator::class]);
```

---

## Customizing Translations (UI Strings)

The package ships with English and Arabic UI strings (button labels, notifications). To customize:

```bash
php artisan vendor:publish --tag="filament-ai-translate-translations"
```

---

## Filament Version Compatibility

This package works with Filament v3, v4, and v5. It uses Filament v5 namespaces internally and includes an automatic compatibility layer (`FilamentCompat.php`) that aliases the older namespace classes on v3/v4. No extra configuration needed — install and go.

## License

MIT License. See [LICENSE](LICENSE) for details.
