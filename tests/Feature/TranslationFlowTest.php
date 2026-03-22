<?php

use Badrsh\FilamentAiAutofill\Contracts\Translator;
use Badrsh\FilamentAiAutofill\Tests\Fixtures\FakeTranslator;
use Badrsh\FilamentAiAutofill\Tests\Fixtures\FailingTranslator;
use Badrsh\FilamentAiAutofill\Translators\NullTranslator;

test('translator is resolved from container using config', function () {
    config(['filament-ai-autofill.translator' => NullTranslator::class]);

    $translator = app(Translator::class);

    expect($translator)->toBeInstanceOf(NullTranslator::class);
});

test('fake translator is resolved when configured', function () {
    config(['filament-ai-autofill.translator' => FakeTranslator::class]);

    $translator = app(Translator::class);

    expect($translator)->toBeInstanceOf(FakeTranslator::class);

    $result = $translator->translate(['title' => 'مرحبا'], 'ar', ['en']);

    expect($result)->toBe([
        'title' => ['en' => '[en] مرحبا'],
    ]);
});

test('failing translator is resolved and throws on translate', function () {
    config(['filament-ai-autofill.translator' => FailingTranslator::class]);

    $translator = app(Translator::class);

    expect($translator)->toBeInstanceOf(FailingTranslator::class);

    expect(fn () => $translator->translate(['title' => 'مرحبا'], 'ar', ['en']))
        ->toThrow(Exception::class, 'Translation service unavailable');
});

test('end-to-end: fake translator translates multiple fields to multiple locales', function () {
    config(['filament-ai-autofill.translator' => FakeTranslator::class]);

    $translator = app(Translator::class);

    $result = $translator->translate(
        [
            'title' => 'عنوان المقال',
            'description' => 'وصف طويل للمقال',
            'slug' => 'معرف-المقال',
        ],
        'ar',
        ['en', 'fr', 'de'],
    );

    // Verify the structure: each source field has all target locales
    expect($result)->toHaveCount(3);
    expect($result['title'])->toHaveCount(3);
    expect($result['description'])->toHaveCount(3);
    expect($result['slug'])->toHaveCount(3);

    // Verify content
    expect($result['title']['en'])->toBe('[en] عنوان المقال');
    expect($result['title']['fr'])->toBe('[fr] عنوان المقال');
    expect($result['title']['de'])->toBe('[de] عنوان المقال');
    expect($result['description']['en'])->toBe('[en] وصف طويل للمقال');
    expect($result['slug']['fr'])->toBe('[fr] معرف-المقال');
});

test('end-to-end: field mapping + translator work together', function () {
    config(['filament-ai-autofill.translator' => FakeTranslator::class]);

    $translator = app(Translator::class);

    // Simulate what the batch action does internally:
    // 1. Build field mapping
    $fieldMap = \Badrsh\FilamentAiAutofill\Support\FieldMapper::forFields(
        ['title', 'description'],
        'ar',
        ['en', 'fr'],
        \Badrsh\FilamentAiAutofill\Enums\FieldNamingStrategy::Suffix,
    );

    // 2. Gather source data
    $sourceData = [];
    foreach ($fieldMap->getSourceFields() as $sourceField) {
        $sourceData[$sourceField] = "Arabic text for {$sourceField}";
    }

    // 3. Translate
    $translations = $translator->translate($sourceData, $fieldMap->sourceLocale, $fieldMap->targetLocales);

    // 4. Distribute to targets (simulate $set)
    $formState = [];
    foreach ($translations as $sourceField => $localeTranslations) {
        foreach ($localeTranslations as $locale => $translatedValue) {
            $targetField = $fieldMap->getTargetField($sourceField, $locale);
            if ($targetField !== null) {
                $formState[$targetField] = $translatedValue;
            }
        }
    }

    // Verify the final form state has correct target fields populated
    expect($formState)->toBe([
        'title_en' => '[en] Arabic text for title',
        'title_fr' => '[fr] Arabic text for title',
        'description_en' => '[en] Arabic text for description',
        'description_fr' => '[fr] Arabic text for description',
    ]);
});

test('end-to-end: dot notation field mapping + translator', function () {
    config(['filament-ai-autofill.translator' => FakeTranslator::class]);

    $translator = app(Translator::class);

    $fieldMap = \Badrsh\FilamentAiAutofill\Support\FieldMapper::forFields(
        ['title.ar', 'body.ar'],
        'ar',
        ['en'],
        \Badrsh\FilamentAiAutofill\Enums\FieldNamingStrategy::DotNotation,
    );

    $sourceData = [
        'title.ar' => 'عنوان',
        'body.ar' => 'محتوى',
    ];

    $translations = $translator->translate($sourceData, 'ar', ['en']);

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

test('end-to-end: explicit mapping + translator', function () {
    config(['filament-ai-autofill.translator' => FakeTranslator::class]);

    $translator = app(Translator::class);

    $fieldMap = \Badrsh\FilamentAiAutofill\Support\FieldMapper::fromExplicitMapping(
        [
            'name' => ['name_en', 'name_fr'],
            'bio' => ['bio_en', 'bio_fr'],
        ],
        'ar',
        ['en', 'fr'],
    );

    $sourceData = ['name' => 'أحمد', 'bio' => 'مبرمج'];
    $translations = $translator->translate($sourceData, 'ar', ['en', 'fr']);

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
        'name_en' => '[en] أحمد',
        'name_fr' => '[fr] أحمد',
        'bio_en' => '[en] مبرمج',
        'bio_fr' => '[fr] مبرمج',
    ]);
});

test('end-to-end: config values drive the translation flow', function () {
    config([
        'filament-ai-autofill.translator' => FakeTranslator::class,
        'filament-ai-autofill.source_locale' => 'en',
        'filament-ai-autofill.target_locales' => ['ar', 'fr'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $sourceLocale = config('filament-ai-autofill.source_locale');
    $targetLocales = config('filament-ai-autofill.target_locales');
    $strategy = \Badrsh\FilamentAiAutofill\Enums\FieldNamingStrategy::from(
        config('filament-ai-autofill.field_naming')
    );

    $fieldMap = \Badrsh\FilamentAiAutofill\Support\FieldMapper::forFields(
        ['title'],
        $sourceLocale,
        $targetLocales,
        $strategy,
    );

    $translator = app(Translator::class);
    $translations = $translator->translate(['title' => 'Hello World'], $sourceLocale, $targetLocales);

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
        'title_ar' => '[ar] Hello World',
        'title_fr' => '[fr] Hello World',
    ]);
});
