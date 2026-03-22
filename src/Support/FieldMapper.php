<?php

namespace Badrsh\FilamentAiAutofill\Support;

use Badrsh\FilamentAiAutofill\Enums\FieldNamingStrategy;

class FieldMapper
{
    /**
     * Build a TranslationConfig by mapping source fields to target fields.
     *
     * @param  array<int, string>  $sourceFields  The source field names from the form.
     * @param  string  $sourceLocale  The source locale code.
     * @param  array<int, string>  $targetLocales  The target locale codes.
     * @param  FieldNamingStrategy  $strategy  The naming strategy to use.
     */
    public static function forFields(
        array $sourceFields,
        string $sourceLocale,
        array $targetLocales,
        FieldNamingStrategy $strategy = FieldNamingStrategy::AutoDetect,
    ): TranslationConfig {
        if ($strategy === FieldNamingStrategy::AutoDetect) {
            $strategy = static::detectStrategy($sourceFields, $sourceLocale);
        }

        $fieldMap = match ($strategy) {
            FieldNamingStrategy::Suffix => static::mapSuffix($sourceFields, $sourceLocale, $targetLocales),
            FieldNamingStrategy::DotNotation => static::mapDotNotation($sourceFields, $sourceLocale, $targetLocales),
            default => static::mapSuffix($sourceFields, $sourceLocale, $targetLocales),
        };

        return new TranslationConfig($sourceLocale, $targetLocales, $fieldMap);
    }

    /**
     * Build a TranslationConfig from an explicit mapping array.
     *
     * @param  array<string, array<int, string>>  $mapping  [sourceField => [targetField1, targetField2, ...]]
     * @param  string  $sourceLocale  The source locale code.
     * @param  array<int, string>  $targetLocales  The target locale codes.
     */
    public static function fromExplicitMapping(
        array $mapping,
        string $sourceLocale,
        array $targetLocales,
    ): TranslationConfig {
        $fieldMap = [];

        foreach ($mapping as $sourceField => $targetFields) {
            foreach ($targetFields as $index => $targetField) {
                $locale = $targetLocales[$index] ?? null;

                if ($locale !== null) {
                    $fieldMap[$sourceField][$locale] = $targetField;
                }
            }
        }

        return new TranslationConfig($sourceLocale, $targetLocales, $fieldMap);
    }

    /**
     * Detect naming strategy based on field names.
     */
    protected static function detectStrategy(array $sourceFields, string $sourceLocale): FieldNamingStrategy
    {
        foreach ($sourceFields as $field) {
            // Check for dot notation: field.locale or field->locale
            if (str_contains($field, ".{$sourceLocale}") || str_contains($field, "->{$sourceLocale}")) {
                return FieldNamingStrategy::DotNotation;
            }

            // Check for suffix: field_locale (only if locale is at the end)
            if (str_ends_with($field, "_{$sourceLocale}")) {
                return FieldNamingStrategy::Suffix;
            }
        }

        // Default to suffix when source fields have no locale indicator
        // (e.g., source locale fields have no suffix: "title" is the ar field)
        return FieldNamingStrategy::Suffix;
    }

    /**
     * Map fields using suffix convention.
     *
     * Source fields can either:
     * - Have no suffix (e.g., "title" for source locale 'ar')
     * - Have source locale suffix (e.g., "title_ar")
     *
     * Target fields get the target locale suffix (e.g., "title_en").
     *
     * @return array<string, array<string, string>>
     */
    protected static function mapSuffix(array $sourceFields, string $sourceLocale, array $targetLocales): array
    {
        $fieldMap = [];

        foreach ($sourceFields as $sourceField) {
            $baseName = $sourceField;

            // Strip source locale suffix if present
            $suffixPattern = "_{$sourceLocale}";
            if (str_ends_with($sourceField, $suffixPattern)) {
                $baseName = substr($sourceField, 0, -strlen($suffixPattern));
            }

            foreach ($targetLocales as $locale) {
                $fieldMap[$sourceField][$locale] = "{$baseName}_{$locale}";
            }
        }

        return $fieldMap;
    }

    /**
     * Map fields using dot notation convention.
     *
     * Source: title.ar or title->ar
     * Target: title.en or title->en
     *
     * @return array<string, array<string, string>>
     */
    protected static function mapDotNotation(array $sourceFields, string $sourceLocale, array $targetLocales): array
    {
        $fieldMap = [];

        foreach ($sourceFields as $sourceField) {
            foreach ($targetLocales as $locale) {
                // Handle both . and -> notation
                if (str_contains($sourceField, "->{$sourceLocale}")) {
                    $targetField = str_replace("->{$sourceLocale}", "->{$locale}", $sourceField);
                } elseif (str_contains($sourceField, ".{$sourceLocale}")) {
                    $targetField = str_replace(".{$sourceLocale}", ".{$locale}", $sourceField);
                } else {
                    // Fallback: append dot notation
                    $targetField = "{$sourceField}.{$locale}";
                }

                $fieldMap[$sourceField][$locale] = $targetField;
            }
        }

        return $fieldMap;
    }
}
