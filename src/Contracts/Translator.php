<?php

namespace Badrsh\FilamentAiTranslate\Contracts;

interface Translator
{
    /**
     * Translate an array of key-value pairs from one locale to multiple target locales.
     *
     * @param  array<string, string>  $values  Field name => text to translate.
     * @param  string  $sourceLocale  Source language code (e.g., 'ar').
     * @param  array<int, string>  $targetLocales  Target language codes (e.g., ['en', 'fr']).
     * @return array<string, array<string, string>>  [field => [locale => translated_text]]
     */
    public function translate(array $values, string $sourceLocale, array $targetLocales): array;
}
