<?php

use Badrsh\FilamentAiAutofill\Tests\Fixtures\FakeTranslator;
use Badrsh\FilamentAiAutofill\Support\FieldMapper;
use Badrsh\FilamentAiAutofill\Enums\FieldNamingStrategy;

/*
|--------------------------------------------------------------------------
| Translation Key Normalization Tests
|--------------------------------------------------------------------------
|
| These tests verify that when AI translators return keys with stripped
| locale suffixes (e.g., "title" instead of "title.ar"), the translation
| distribution logic can still map them back to the correct target fields.
|
*/

test('dot notation: AI returns keys without locale suffix and normalization fixes them', function () {
    // Simulate what TranslateBatchAction does internally
    $fieldMap = FieldMapper::forFields(
        ['title.ar', 'body.ar'],
        'ar',
        ['en'],
        FieldNamingStrategy::DotNotation,
    );

    // AI returns "title" instead of "title.ar" (common AI behavior)
    $aiTranslations = [
        'title' => ['en' => 'Hello World'],
        'body' => ['en' => 'Content here'],
    ];

    // Normalize keys (same logic as in TranslateBatchAction::handleBatchTranslation)
    $sourceLocale = 'ar';
    $sourceFieldKeys = $fieldMap->getSourceFields();
    $keyLookup = [];

    foreach ($sourceFieldKeys as $field) {
        $keyLookup[$field] = $field;

        $stripped = preg_replace('/\.' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }

        $stripped = preg_replace('/_' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }
    }

    $normalizedTranslations = [];
    foreach ($aiTranslations as $key => $localeTranslations) {
        $resolvedKey = $keyLookup[$key] ?? $key;
        $normalizedTranslations[$resolvedKey] = $localeTranslations;
    }

    // Now distribute to form state
    $formState = [];
    foreach ($normalizedTranslations as $sourceField => $localeTranslations) {
        foreach ($localeTranslations as $locale => $translatedValue) {
            $targetField = $fieldMap->getTargetField($sourceField, $locale);
            if ($targetField !== null) {
                $formState[$targetField] = $translatedValue;
            }
        }
    }

    expect($formState)->toBe([
        'title.en' => 'Hello World',
        'body.en' => 'Content here',
    ]);
});

test('suffix notation: AI returns keys without locale suffix and normalization fixes them', function () {
    $fieldMap = FieldMapper::forFields(
        ['title_ar', 'body_ar'],
        'ar',
        ['en'],
        FieldNamingStrategy::Suffix,
    );

    // AI strips _ar suffix
    $aiTranslations = [
        'title' => ['en' => 'Hello World'],
        'body' => ['en' => 'Content here'],
    ];

    $sourceLocale = 'ar';
    $sourceFieldKeys = $fieldMap->getSourceFields();
    $keyLookup = [];

    foreach ($sourceFieldKeys as $field) {
        $keyLookup[$field] = $field;

        $stripped = preg_replace('/\.' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }

        $stripped = preg_replace('/_' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }
    }

    $normalizedTranslations = [];
    foreach ($aiTranslations as $key => $localeTranslations) {
        $resolvedKey = $keyLookup[$key] ?? $key;
        $normalizedTranslations[$resolvedKey] = $localeTranslations;
    }

    $formState = [];
    foreach ($normalizedTranslations as $sourceField => $localeTranslations) {
        foreach ($localeTranslations as $locale => $translatedValue) {
            $targetField = $fieldMap->getTargetField($sourceField, $locale);
            if ($targetField !== null) {
                $formState[$targetField] = $translatedValue;
            }
        }
    }

    expect($formState)->toBe([
        'title_en' => 'Hello World',
        'body_en' => 'Content here',
    ]);
});

test('normalization preserves exact keys when AI returns them correctly', function () {
    $fieldMap = FieldMapper::forFields(
        ['title.ar', 'body.ar'],
        'ar',
        ['en'],
        FieldNamingStrategy::DotNotation,
    );

    // AI correctly returns "title.ar" as key (ideal behavior)
    $aiTranslations = [
        'title.ar' => ['en' => 'Hello World'],
        'body.ar' => ['en' => 'Content here'],
    ];

    $sourceLocale = 'ar';
    $sourceFieldKeys = $fieldMap->getSourceFields();
    $keyLookup = [];

    foreach ($sourceFieldKeys as $field) {
        $keyLookup[$field] = $field;

        $stripped = preg_replace('/\.' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }
    }

    $normalizedTranslations = [];
    foreach ($aiTranslations as $key => $localeTranslations) {
        $resolvedKey = $keyLookup[$key] ?? $key;
        $normalizedTranslations[$resolvedKey] = $localeTranslations;
    }

    $formState = [];
    foreach ($normalizedTranslations as $sourceField => $localeTranslations) {
        foreach ($localeTranslations as $locale => $translatedValue) {
            $targetField = $fieldMap->getTargetField($sourceField, $locale);
            if ($targetField !== null) {
                $formState[$targetField] = $translatedValue;
            }
        }
    }

    expect($formState)->toBe([
        'title.en' => 'Hello World',
        'body.en' => 'Content here',
    ]);
});

test('normalization handles bare field names that have no locale suffix', function () {
    // Suffix strategy with source = 'ar' where source fields have no suffix
    $fieldMap = FieldMapper::forFields(
        ['title', 'description'],
        'ar',
        ['en'],
        FieldNamingStrategy::Suffix,
    );

    // AI returns keys as-is (no suffix to strip)
    $aiTranslations = [
        'title' => ['en' => 'Hello World'],
        'description' => ['en' => 'Content here'],
    ];

    $sourceLocale = 'ar';
    $sourceFieldKeys = $fieldMap->getSourceFields();
    $keyLookup = [];

    foreach ($sourceFieldKeys as $field) {
        $keyLookup[$field] = $field;

        $stripped = preg_replace('/\.' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }

        $stripped = preg_replace('/_' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }
    }

    $normalizedTranslations = [];
    foreach ($aiTranslations as $key => $localeTranslations) {
        $resolvedKey = $keyLookup[$key] ?? $key;
        $normalizedTranslations[$resolvedKey] = $localeTranslations;
    }

    $formState = [];
    foreach ($normalizedTranslations as $sourceField => $localeTranslations) {
        foreach ($localeTranslations as $locale => $translatedValue) {
            $targetField = $fieldMap->getTargetField($sourceField, $locale);
            if ($targetField !== null) {
                $formState[$targetField] = $translatedValue;
            }
        }
    }

    expect($formState)->toBe([
        'title_en' => 'Hello World',
        'description_en' => 'Content here',
    ]);
});

test('dot notation end-to-end flow with FakeTranslator matches real field names', function () {
    $translator = new FakeTranslator();

    $fieldMap = FieldMapper::forFields(
        ['title.ar', 'body.ar'],
        'ar',
        ['en'],
        FieldNamingStrategy::DotNotation,
    );

    // Source data uses full dot-notation keys
    $sourceData = [
        'title.ar' => 'عنوان',
        'body.ar' => 'محتوى',
    ];

    $translations = $translator->translate($sourceData, 'ar', ['en']);

    // FakeTranslator preserves keys exactly — so "title.ar" stays "title.ar"
    expect($translations)->toHaveKey('title.ar');
    expect($translations)->toHaveKey('body.ar');

    // Distribute
    $formState = [];
    foreach ($translations as $sourceField => $localeTranslations) {
        foreach ($localeTranslations as $locale => $translatedValue) {
            $targetField = $fieldMap->getTargetField($sourceField, $locale);
            if ($targetField !== null) {
                $formState[$targetField] = $translatedValue;
            }
        }
    }

    expect($formState)->toBe([
        'title.en' => '[en] عنوان',
        'body.en' => '[en] محتوى',
    ]);
});

test('normalization handles multiple target locales with AI key stripping', function () {
    $fieldMap = FieldMapper::forFields(
        ['title.ar'],
        'ar',
        ['en', 'fr', 'de'],
        FieldNamingStrategy::DotNotation,
    );

    // AI strips .ar and returns translations for all locales
    $aiTranslations = [
        'title' => ['en' => 'Title', 'fr' => 'Titre', 'de' => 'Titel'],
    ];

    $sourceLocale = 'ar';
    $sourceFieldKeys = $fieldMap->getSourceFields();
    $keyLookup = [];

    foreach ($sourceFieldKeys as $field) {
        $keyLookup[$field] = $field;
        $stripped = preg_replace('/\.' . preg_quote($sourceLocale, '/') . '$/', '', $field);
        if ($stripped !== $field) {
            $keyLookup[$stripped] = $field;
        }
    }

    $normalizedTranslations = [];
    foreach ($aiTranslations as $key => $localeTranslations) {
        $resolvedKey = $keyLookup[$key] ?? $key;
        $normalizedTranslations[$resolvedKey] = $localeTranslations;
    }

    $formState = [];
    foreach ($normalizedTranslations as $sourceField => $localeTranslations) {
        foreach ($localeTranslations as $locale => $translatedValue) {
            $targetField = $fieldMap->getTargetField($sourceField, $locale);
            if ($targetField !== null) {
                $formState[$targetField] = $translatedValue;
            }
        }
    }

    expect($formState)->toBe([
        'title.en' => 'Title',
        'title.fr' => 'Titre',
        'title.de' => 'Titel',
    ]);
});
