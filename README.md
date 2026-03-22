# Filament AI Autofill

AI-powered auto-translation for [Filament](https://filamentphp.com) form fields. Click a button, and your content is translated from one language to many, using OpenAI or any custom AI translator.

![Filament v4/v5](https://img.shields.io/badge/Filament-v4%20%7C%20v5-blue)
![Laravel](https://img.shields.io/badge/Laravel-10%2F11%2F12%2F13-red)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple)
![License](https://img.shields.io/badge/license-MIT-green)

## What It Does

You have a Filament form with fields in Arabic (or any source language). You want the English (or other) fields filled in automatically. This package adds:

1. **A sparkle ✨ icon** on individual fields — click it to translate that one field
2. **An "Auto-translate All" button** — translates every source field in one API call
3. **A tab helper** — builds AR / EN / FR tabs with translation built in
4. **Smart auto-detection** — automatically picks sparkle or batch mode based on field count

All translations happen via the Laravel AI SDK when available (supports OpenAI, Anthropic, Gemini, and more). If `laravel/ai` is not installed or your environment doesn't meet its requirements (PHP 8.3+, Laravel 12+), the package automatically falls back to the built-in OpenAI translator. No configuration needed — it just works.


## Example  1

![chrome_0GEJ4nfliU](https://github.com/user-attachments/assets/230ffb10-a035-4278-ad56-b6a4b7e11c7d)

## Example  2

![chrome_nIofIM1GQt](https://github.com/user-attachments/assets/f665ec0c-6c09-4780-836e-a6cfbaa83415)


## Example 3

![chrome_H7rE98Xl19](https://github.com/user-attachments/assets/7da59d05-1daa-4f99-9293-b21bfcd55607)

## Requirements

| Dependency | Versions |
|------------|----------|
| PHP | 8.2+ |
| Laravel | 10, 11, 12, 13 |
| Filament | 4.x, 5.x |

## Installation

### Step 1: Install the package

```bash
composer require badrsh/filament-ai-autofill
```

### Step 2: Publish the config

```bash
php artisan vendor:publish --tag="filament-ai-autofill-config"
```

### Step 3: Add your OpenAI key to `.env`

```env
OPENAI_API_KEY=sk-your-key-here
```

That's it. The package works out of the box. It will automatically use the Laravel AI SDK if your environment supports it (PHP 8.3+, Laravel 12+), or fall back to the built-in OpenAI translator otherwise.

> **Tip:** To enable multi-provider support (Anthropic, Gemini, etc.), just run `composer require laravel/ai`. The package will detect it and switch automatically.

That's it. The package works out of the box with any Filament form.

### Optional: Panel Plugin

If you want to override source/target locales at the panel level (instead of config):

```php
use Badrsh\FilamentAiAutofill\FilamentAiAutofillPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentAiAutofillPlugin::make()
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
use Badrsh\FilamentAiAutofill\Concerns\HasTranslatableFields;

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
    withBatchAction: null,       // null = auto-detect (true when multiple fields)
    withFieldActions: null,      // null = auto-detect (true when single field)
    sourceLocale: 'ar',          // Override source locale
    targetLocales: ['en', 'fr'], // Override target locales
    locales: ['ar', 'en', 'fr'], // Override full locale list
);
```

**Smart auto-detection:** When you leave `withBatchAction` and `withFieldActions` as `null` (the default), the package automatically chooses the best mode:
- **1 field** → sparkle icon on the field (per-field action)
- **2+ fields** → batch "Auto-translate All" button

You can override this by passing `true` or `false` explicitly. Setting one automatically sets the other to its opposite.

**Textarea & RichEditor support:** The sparkle action works on all field types. Fields that don't support suffix icons (like `Textarea`) use a hint icon (top-right) instead.

### Approach 2: Inline Per-Field Action

Add a sparkle icon (✨) to any individual field. When clicked, it translates that single field.

```php
use Badrsh\FilamentAiAutofill\Actions\TranslateFieldAction;

Forms\Components\TextInput::make('title')
    ->suffixAction(
        TranslateFieldAction::make()
            ->targetFields(['en' => 'title_en', 'fr' => 'title_fr'])
    ),
```

**What happens:**
- A sparkle icon appears on the right side of the `title` field
- Clicking it sends the Arabic text to your AI translator and fills `title_en` and `title_fr`

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
use Badrsh\FilamentAiAutofill\Actions\TranslateBatchAction;

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

After publishing, edit `config/filament-ai-autofill.php`:

```php
return [
    // The translator class (must implement Badrsh\FilamentAiAutofill\Contracts\Translator)
    // Default: LaravelAiTranslator (auto-falls back to OpenAiTranslator if laravel/ai is not installed)
    'translator' => \Badrsh\FilamentAiAutofill\Translators\LaravelAiTranslator::class,

    // The language your content is written in
    'source_locale' => 'ar',

    // The languages to translate into
    'target_locales' => ['en'],

    // How your form fields are named (see "Field Naming" below)
    'field_naming' => 'auto',    // 'suffix', 'dot', or 'auto'

    // Ask before overwriting fields that already have content
    'confirm_overwrite' => true,

    // Laravel AI SDK settings (for LaravelAiTranslator, requires laravel/ai)
    'laravel_ai' => [
        'timeout' => env('AI_TIMEOUT', 60),
    ],

    // OpenAI settings (for OpenAiTranslator, all configurable via .env)
    'openai' => [
        'key'      => env('OPENAI_API_KEY'),
        'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout'  => env('OPENAI_TIMEOUT', 60),
    ],
];
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OPENAI_API_KEY` | *(required)* | Your OpenAI API key |
| `OPENAI_MODEL` | `gpt-4o-mini` | The model to use for translations |
| `OPENAI_BASE_URL` | `https://api.openai.com/v1` | API base URL (change for proxies or compatible APIs) |
| `OPENAI_TIMEOUT` | `60` | HTTP timeout in seconds per request. Increase if you hit timeouts on large texts |
| `AI_TIMEOUT` | `60` | HTTP timeout for the Laravel AI SDK translator |

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
use Badrsh\FilamentAiAutofill\Contracts\Translator;

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
config(['filament-ai-autofill.translator' => \Badrsh\FilamentAiAutofill\Translators\NullTranslator::class]);
```

---

## Customizing Translations (UI Strings)

The package ships with English and Arabic UI strings (button labels, notifications). To customize:

```bash
php artisan vendor:publish --tag="filament-ai-autofill-translations"
```

---

## Filament Version Compatibility

This package works with Filament v4 and v5. It uses Filament v5 namespaces internally and includes a compatibility layer (`FilamentCompat.php`) that aliases older namespace classes when needed. No extra configuration needed — install and go.

## License

MIT License. See [LICENSE](LICENSE) for details.
