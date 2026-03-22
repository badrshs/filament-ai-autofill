<?php

namespace Badrsh\FilamentAiAutofill\Translators;

use Badrsh\FilamentAiAutofill\Contracts\Translator;

/**
 * A no-op translator useful for testing and development.
 * Returns empty translations for all fields.
 */
class NullTranslator implements Translator
{
    /**
     * @param  array<string, string>  $values
     * @param  array<int, string>  $targetLocales
     * @return array<string, array<string, string>>
     */
    public function translate(array $values, string $sourceLocale, array $targetLocales): array
    {
        $result = [];

        foreach ($values as $field => $value) {
            foreach ($targetLocales as $locale) {
                $result[$field][$locale] = '';
            }
        }

        return $result;
    }
}
