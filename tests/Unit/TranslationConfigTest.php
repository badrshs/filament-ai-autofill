<?php

use Badrsh\FilamentAiAutofill\Support\TranslationConfig;

test('translation config stores source locale, target locales, and field map', function () {
    $config = new TranslationConfig('ar', ['en', 'fr'], [
        'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
        'body' => ['en' => 'body_en', 'fr' => 'body_fr'],
    ]);

    expect($config->sourceLocale)->toBe('ar');
    expect($config->targetLocales)->toBe(['en', 'fr']);
    expect($config->fieldMap)->toHaveCount(2);
});

test('getSourceFields returns all source field names', function () {
    $config = new TranslationConfig('ar', ['en'], [
        'title' => ['en' => 'title_en'],
        'body' => ['en' => 'body_en'],
        'slug' => ['en' => 'slug_en'],
    ]);

    expect($config->getSourceFields())->toBe(['title', 'body', 'slug']);
});

test('getTargetField returns correct target for a given source and locale', function () {
    $config = new TranslationConfig('ar', ['en', 'fr'], [
        'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
    ]);

    expect($config->getTargetField('title', 'en'))->toBe('title_en');
    expect($config->getTargetField('title', 'fr'))->toBe('title_fr');
});

test('getTargetField returns null for unknown source field', function () {
    $config = new TranslationConfig('ar', ['en'], [
        'title' => ['en' => 'title_en'],
    ]);

    expect($config->getTargetField('nonexistent', 'en'))->toBeNull();
});

test('getTargetField returns null for unknown locale', function () {
    $config = new TranslationConfig('ar', ['en'], [
        'title' => ['en' => 'title_en'],
    ]);

    expect($config->getTargetField('title', 'fr'))->toBeNull();
});

test('getTargetFieldsForLocale returns all mappings for a locale', function () {
    $config = new TranslationConfig('ar', ['en', 'fr'], [
        'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
        'body' => ['en' => 'body_en', 'fr' => 'body_fr'],
    ]);

    expect($config->getTargetFieldsForLocale('en'))->toBe([
        'title' => 'title_en',
        'body' => 'body_en',
    ]);

    expect($config->getTargetFieldsForLocale('fr'))->toBe([
        'title' => 'title_fr',
        'body' => 'body_fr',
    ]);
});

test('getTargetFieldsForLocale returns empty for unknown locale', function () {
    $config = new TranslationConfig('ar', ['en'], [
        'title' => ['en' => 'title_en'],
    ]);

    expect($config->getTargetFieldsForLocale('de'))->toBe([]);
});

test('getAllTargetFields returns unique target field names', function () {
    $config = new TranslationConfig('ar', ['en', 'fr'], [
        'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
        'body' => ['en' => 'body_en', 'fr' => 'body_fr'],
    ]);

    $all = $config->getAllTargetFields();
    sort($all);

    expect($all)->toBe(['body_en', 'body_fr', 'title_en', 'title_fr']);
});

test('empty field map returns empty source fields and targets', function () {
    $config = new TranslationConfig('ar', ['en'], []);

    expect($config->getSourceFields())->toBe([]);
    expect($config->getAllTargetFields())->toBe([]);
    expect($config->getTargetFieldsForLocale('en'))->toBe([]);
});
