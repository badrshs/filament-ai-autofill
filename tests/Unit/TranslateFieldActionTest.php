<?php

use Badrsh\FilamentAiAutofill\Actions\TranslateFieldAction;

/*
|--------------------------------------------------------------------------
| TranslateFieldAction Config Tests
|--------------------------------------------------------------------------
|
| Tests for the TranslateFieldAction builder methods and configuration.
|
*/

test('fluent builder sets source field', function () {
    $action = TranslateFieldAction::make('test');

    $action->sourceField('title.ar');

    $reflection = new ReflectionProperty($action, 'explicitSourceField');
    expect($reflection->getValue($action))->toBe('title.ar');
});

test('fluent builder sets target fields', function () {
    $action = TranslateFieldAction::make('test');

    $action->targetFields(['en' => 'title.en', 'fr' => 'title.fr']);

    $reflection = new ReflectionProperty($action, 'targetFields');
    expect($reflection->getValue($action))->toBe([
        'en' => 'title.en',
        'fr' => 'title.fr',
    ]);
});

test('fluent builder sets source locale', function () {
    $action = TranslateFieldAction::make('test');

    $action->sourceLocale('ar');

    $reflection = new ReflectionProperty($action, 'sourceLocale');
    expect($reflection->getValue($action))->toBe('ar');
});

test('fluent builder sets target locales', function () {
    $action = TranslateFieldAction::make('test');

    $action->targetLocales(['en', 'fr']);

    $reflection = new ReflectionProperty($action, 'targetLocales');
    expect($reflection->getValue($action))->toBe(['en', 'fr']);
});

test('fluent builder sets translator class', function () {
    $action = TranslateFieldAction::make('test');

    $action->translator(\Badrsh\FilamentAiAutofill\Translators\NullTranslator::class);

    $reflection = new ReflectionProperty($action, 'translatorClass');
    expect($reflection->getValue($action))->toBe(\Badrsh\FilamentAiAutofill\Translators\NullTranslator::class);
});

test('fluent builder sets confirm overwrite', function () {
    $action = TranslateFieldAction::make('test');

    $action->confirmOverwrite(false);

    $reflection = new ReflectionProperty($action, 'confirmOverwrite');
    expect($reflection->getValue($action))->toBeFalse();
});

test('resolveTargetFieldMap handles locale-keyed arrays', function () {
    $action = TranslateFieldAction::make('test');

    $action->targetFields(['en' => 'title_en', 'fr' => 'title_fr']);

    $method = new ReflectionMethod($action, 'resolveTargetFieldMap');
    $result = $method->invoke($action, ['en', 'fr']);

    expect($result)->toBe([
        'en' => 'title_en',
        'fr' => 'title_fr',
    ]);
});

test('resolveTargetFieldMap handles indexed arrays', function () {
    $action = TranslateFieldAction::make('test');

    $action->targetFields(['title_en', 'title_fr']);

    $method = new ReflectionMethod($action, 'resolveTargetFieldMap');
    $result = $method->invoke($action, ['en', 'fr']);

    expect($result)->toBe([
        'en' => 'title_en',
        'fr' => 'title_fr',
    ]);
});

test('resolveTargetFieldMap returns empty for no target fields', function () {
    $action = TranslateFieldAction::make('test');

    $method = new ReflectionMethod($action, 'resolveTargetFieldMap');
    $result = $method->invoke($action, ['en']);

    expect($result)->toBeEmpty();
});

test('getSourceFieldName returns explicit source field when set', function () {
    $action = TranslateFieldAction::make('test');

    $action->sourceField('title.ar');

    $method = new ReflectionMethod($action, 'getSourceFieldName');
    expect($method->invoke($action))->toBe('title.ar');
});

test('getSourceFieldName returns empty string when no source set and no component', function () {
    $action = TranslateFieldAction::make('test');

    $method = new ReflectionMethod($action, 'getSourceFieldName');
    expect($method->invoke($action))->toBe('');
});

test('resolveSourceLocale falls back to config', function () {
    config(['filament-ai-autofill.source_locale' => 'fr']);

    $action = TranslateFieldAction::make('test');

    $method = new ReflectionMethod($action, 'resolveSourceLocale');
    expect($method->invoke($action))->toBe('fr');
});

test('resolveSourceLocale uses explicit value over config', function () {
    config(['filament-ai-autofill.source_locale' => 'fr']);

    $action = TranslateFieldAction::make('test');
    $action->sourceLocale('ar');

    $method = new ReflectionMethod($action, 'resolveSourceLocale');
    expect($method->invoke($action))->toBe('ar');
});

test('resolveTargetLocales falls back to config', function () {
    config(['filament-ai-autofill.target_locales' => ['en', 'de']]);

    $action = TranslateFieldAction::make('test');

    $method = new ReflectionMethod($action, 'resolveTargetLocales');
    expect($method->invoke($action))->toBe(['en', 'de']);
});

test('resolveTargetLocales uses explicit value over config', function () {
    config(['filament-ai-autofill.target_locales' => ['en']]);

    $action = TranslateFieldAction::make('test');
    $action->targetLocales(['fr', 'de']);

    $method = new ReflectionMethod($action, 'resolveTargetLocales');
    expect($method->invoke($action))->toBe(['fr', 'de']);
});

test('default action name is translate_field', function () {
    expect(TranslateFieldAction::getDefaultName())->toBe('translate_field');
});

test('fluent builder methods return static for chaining', function () {
    $action = TranslateFieldAction::make('test');

    $result = $action
        ->sourceField('title.ar')
        ->targetFields(['en' => 'title.en'])
        ->sourceLocale('ar')
        ->targetLocales(['en'])
        ->confirmOverwrite(true);

    expect($result)->toBeInstanceOf(TranslateFieldAction::class);
});
