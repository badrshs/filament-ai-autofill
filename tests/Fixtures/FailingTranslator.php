<?php

namespace Badrsh\FilamentAiAutofill\Tests\Fixtures;

use Badrsh\FilamentAiAutofill\Contracts\Translator;
use Exception;

/**
 * A translator that always throws, for testing error handling paths.
 */
class FailingTranslator implements Translator
{
    public function translate(array $values, string $sourceLocale, array $targetLocales): array
    {
        throw new Exception('Translation service unavailable');
    }
}
