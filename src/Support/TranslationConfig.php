<?php

namespace Badrsh\FilamentAiAutofill\Support;

/**
 * Immutable value object holding the translation field mapping configuration.
 */
class TranslationConfig
{
    /**
     * @param  string  $sourceLocale  The source language code.
     * @param  array<int, string>  $targetLocales  The target language codes.
     * @param  array<string, array<string, string>>  $fieldMap  [sourceField => [locale => targetField]]
     */
    public function __construct(
        public readonly string $sourceLocale,
        public readonly array $targetLocales,
        public readonly array $fieldMap,
    ) {}

    /**
     * Get all source field names.
     *
     * @return array<int, string>
     */
    public function getSourceFields(): array
    {
        return array_keys($this->fieldMap);
    }

    /**
     * Get the target field name for a given source field and locale.
     */
    public function getTargetField(string $sourceField, string $locale): ?string
    {
        return $this->fieldMap[$sourceField][$locale] ?? null;
    }

    /**
     * Get all target field names for a specific locale.
     *
     * @return array<string, string>  [sourceField => targetField]
     */
    public function getTargetFieldsForLocale(string $locale): array
    {
        $result = [];

        foreach ($this->fieldMap as $sourceField => $localeMap) {
            if (isset($localeMap[$locale])) {
                $result[$sourceField] = $localeMap[$locale];
            }
        }

        return $result;
    }

    /**
     * Get all target field names across all locales.
     *
     * @return array<int, string>
     */
    public function getAllTargetFields(): array
    {
        $fields = [];

        foreach ($this->fieldMap as $localeMap) {
            foreach ($localeMap as $targetField) {
                $fields[] = $targetField;
            }
        }

        return array_unique($fields);
    }
}
