<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Translator Driver
    |--------------------------------------------------------------------------
    |
    | The class responsible for performing translations. Must implement
    | Badrsh\FilamentAiTranslate\Contracts\Translator.
    |
    | Built-in options:
    | - Badrsh\FilamentAiTranslate\Translators\OpenAiTranslator::class
    | - Badrsh\FilamentAiTranslate\Translators\NullTranslator::class
    |
    */

    'translator' => Badrsh\FilamentAiTranslate\Translators\OpenAiTranslator::class,

    /*
    |--------------------------------------------------------------------------
    | Source Locale
    |--------------------------------------------------------------------------
    |
    | The default language code that content is written in. This is the locale
    | that will be used as the source for translations.
    |
    */

    'source_locale' => 'ar',

    /*
    |--------------------------------------------------------------------------
    | Target Locales
    |--------------------------------------------------------------------------
    |
    | The language codes to translate into. These will be the destination
    | languages for all translation actions.
    |
    */

    'target_locales' => ['en'],

    /*
    |--------------------------------------------------------------------------
    | Field Naming Strategy
    |--------------------------------------------------------------------------
    |
    | How translatable field names are structured in your forms.
    |
    | - 'suffix'  : title (source) → title_en (target)
    | - 'dot'     : title.ar (source) → title.en (target)
    | - 'auto'    : Auto-detect based on field names
    |
    */

    'field_naming' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Confirm Before Overwrite
    |--------------------------------------------------------------------------
    |
    | When true, users will see a confirmation modal before overwriting
    | target fields that already contain content.
    |
    */

    'confirm_overwrite' => true,

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the built-in OpenAI translator driver.
    |
    */

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

];
