<?php

use Badrsh\FilamentAiAutofill\Enums\FieldNamingStrategy;
use Badrsh\FilamentAiAutofill\Support\FieldMapper;
use Badrsh\FilamentAiAutofill\Support\TranslationConfig;

// ─── FieldNamingStrategy Enum ───

test('field naming strategy has correct values', function () {
    expect(FieldNamingStrategy::Suffix->value)->toBe('suffix');
    expect(FieldNamingStrategy::DotNotation->value)->toBe('dot');
    expect(FieldNamingStrategy::AutoDetect->value)->toBe('auto');
});

test('field naming strategy can be created from string', function () {
    expect(FieldNamingStrategy::from('suffix'))->toBe(FieldNamingStrategy::Suffix);
    expect(FieldNamingStrategy::from('dot'))->toBe(FieldNamingStrategy::DotNotation);
    expect(FieldNamingStrategy::from('auto'))->toBe(FieldNamingStrategy::AutoDetect);
});

test('field naming strategy tryFrom returns null for invalid value', function () {
    expect(FieldNamingStrategy::tryFrom('invalid'))->toBeNull();
});

// ─── FieldMapper: Suffix Strategy ───

test('field mapper maps fields with suffix strategy (no locale suffix on source)', function () {
    $config = FieldMapper::forFields(
        ['title', 'description'],
        'ar',
        ['en', 'fr'],
        FieldNamingStrategy::Suffix,
    );

    expect($config)->toBeInstanceOf(TranslationConfig::class);
    expect($config->sourceLocale)->toBe('ar');
    expect($config->targetLocales)->toBe(['en', 'fr']);
    expect($config->fieldMap)->toBe([
        'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
        'description' => ['en' => 'description_en', 'fr' => 'description_fr'],
    ]);
});

test('field mapper strips source suffix when present', function () {
    $config = FieldMapper::forFields(
        ['title_ar', 'description_ar'],
        'ar',
        ['en'],
        FieldNamingStrategy::Suffix,
    );

    expect($config->fieldMap)->toBe([
        'title_ar' => ['en' => 'title_en'],
        'description_ar' => ['en' => 'description_en'],
    ]);
});

// ─── FieldMapper: Dot Notation Strategy ───

test('field mapper maps fields with dot notation strategy', function () {
    $config = FieldMapper::forFields(
        ['title.ar', 'description.ar'],
        'ar',
        ['en', 'fr'],
        FieldNamingStrategy::DotNotation,
    );

    expect($config->fieldMap)->toBe([
        'title.ar' => ['en' => 'title.en', 'fr' => 'title.fr'],
        'description.ar' => ['en' => 'description.en', 'fr' => 'description.fr'],
    ]);
});

// ─── FieldMapper: Auto Detection ───

test('field mapper auto-detects suffix strategy from bare field names', function () {
    $config = FieldMapper::forFields(
        ['title', 'description'],
        'ar',
        ['en'],
        FieldNamingStrategy::AutoDetect,
    );

    // Bare field names (no .ar, no _ar) → defaults to suffix
    expect($config->fieldMap)->toBe([
        'title' => ['en' => 'title_en'],
        'description' => ['en' => 'description_en'],
    ]);
});

test('field mapper auto-detects dot notation from dot-suffixed field names', function () {
    $config = FieldMapper::forFields(
        ['title.ar', 'body.ar'],
        'ar',
        ['en'],
        FieldNamingStrategy::AutoDetect,
    );

    expect($config->fieldMap)->toBe([
        'title.ar' => ['en' => 'title.en'],
        'body.ar' => ['en' => 'body.en'],
    ]);
});

test('field mapper auto-detects suffix from locale-suffixed field names', function () {
    $config = FieldMapper::forFields(
        ['title_ar', 'body_ar'],
        'ar',
        ['en'],
        FieldNamingStrategy::AutoDetect,
    );

    expect($config->fieldMap)->toBe([
        'title_ar' => ['en' => 'title_en'],
        'body_ar' => ['en' => 'body_en'],
    ]);
});

// ─── FieldMapper: Explicit Mapping ───

test('field mapper builds from explicit mapping', function () {
    $config = FieldMapper::fromExplicitMapping(
        [
            'title' => ['title_en', 'title_fr'],
            'body' => ['body_en', 'body_fr'],
        ],
        'ar',
        ['en', 'fr'],
    );

    expect($config->fieldMap)->toBe([
        'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
        'body' => ['en' => 'body_en', 'fr' => 'body_fr'],
    ]);
});

test('field mapper handles empty source fields', function () {
    $config = FieldMapper::forFields([], 'ar', ['en'], FieldNamingStrategy::Suffix);

    expect($config->fieldMap)->toBe([]);
    expect($config->getSourceFields())->toBe([]);
});

test('field mapper handles many target locales', function () {
    $config = FieldMapper::forFields(
        ['title'],
        'ar',
        ['en', 'fr', 'de', 'es', 'it'],
        FieldNamingStrategy::Suffix,
    );

    expect($config->fieldMap['title'])->toBe([
        'en' => 'title_en',
        'fr' => 'title_fr',
        'de' => 'title_de',
        'es' => 'title_es',
        'it' => 'title_it',
    ]);
});
