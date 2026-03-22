<?php

use Badrsh\FilamentAiAutofill\Translators\NullTranslator;
use Badrsh\FilamentAiAutofill\Tests\Fixtures\FakeTranslator;

test('null translator returns empty strings for all fields and locales', function () {
    $translator = new NullTranslator();

    $result = $translator->translate(
        ['title' => 'مرحبا', 'description' => 'نص'],
        'ar',
        ['en', 'fr'],
    );

    expect($result)->toBe([
        'title' => ['en' => '', 'fr' => ''],
        'description' => ['en' => '', 'fr' => ''],
    ]);
});

test('null translator handles empty values array', function () {
    $translator = new NullTranslator();

    $result = $translator->translate([], 'ar', ['en']);

    expect($result)->toBe([]);
});

test('null translator handles empty target locales', function () {
    $translator = new NullTranslator();

    $result = $translator->translate(['title' => 'مرحبا'], 'ar', []);

    expect($result)->toBe([]);
});

test('null translator handles single field single locale', function () {
    $translator = new NullTranslator();

    $result = $translator->translate(['title' => 'مرحبا'], 'ar', ['en']);

    expect($result)->toBe(['title' => ['en' => '']]);
});

test('fake translator returns predictable prefixed translations', function () {
    $translator = new FakeTranslator();

    $result = $translator->translate(
        ['title' => 'مرحبا', 'description' => 'نص طويل'],
        'ar',
        ['en', 'fr'],
    );

    expect($result)->toBe([
        'title' => ['en' => '[en] مرحبا', 'fr' => '[fr] مرحبا'],
        'description' => ['en' => '[en] نص طويل', 'fr' => '[fr] نص طويل'],
    ]);
});

test('fake translator tracks call count and last parameters', function () {
    $translator = new FakeTranslator();

    $translator->translate(['title' => 'Hello'], 'en', ['ar']);
    $translator->translate(['body' => 'World'], 'en', ['fr', 'de']);

    expect($translator->callCount)->toBe(2);
    expect($translator->lastValues)->toBe(['body' => 'World']);
    expect($translator->lastSourceLocale)->toBe('en');
    expect($translator->lastTargetLocales)->toBe(['fr', 'de']);
});
