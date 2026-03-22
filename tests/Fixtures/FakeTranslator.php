<?php

namespace Badrsh\FilamentAiAutofill\Tests\Fixtures;

use Badrsh\FilamentAiAutofill\Contracts\Translator;

/**
 * A fake translator that returns predictable results for testing.
 * Prefixes each value with the target locale, e.g. "Hello" → "[en] Hello"
 */
class FakeTranslator implements Translator
{
    public array $lastValues = [];
    public string $lastSourceLocale = '';
    public array $lastTargetLocales = [];
    public int $callCount = 0;

    public function translate(array $values, string $sourceLocale, array $targetLocales): array
    {
        $this->lastValues = $values;
        $this->lastSourceLocale = $sourceLocale;
        $this->lastTargetLocales = $targetLocales;
        $this->callCount++;

        $result = [];

        foreach ($values as $field => $value) {
            foreach ($targetLocales as $locale) {
                $result[$field][$locale] = "[{$locale}] {$value}";
            }
        }

        return $result;
    }
}
