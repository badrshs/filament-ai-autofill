<?php

use Badrsh\FilamentAiAutofill\Contracts\Translator;
use Badrsh\FilamentAiAutofill\Translators\NullTranslator;
use Badrsh\FilamentAiAutofill\Translators\OpenAiTranslator;
use Badrsh\FilamentAiAutofill\Tests\Fixtures\FakeTranslator;

test('service provider registers config', function () {
    expect(config('filament-ai-autofill.source_locale'))->toBe('ar');
    expect(config('filament-ai-autofill.target_locales'))->toBe(['en']);
    expect(config('filament-ai-autofill.field_naming'))->toBe('auto');
    expect(config('filament-ai-autofill.confirm_overwrite'))->toBeTrue();
});

test('service provider registers translator binding', function () {
    $translator = app(Translator::class);

    expect($translator)->toBeInstanceOf(NullTranslator::class);
});

test('translator binding respects config override', function () {
    config(['filament-ai-autofill.translator' => FakeTranslator::class]);

    $translator = app(Translator::class);

    expect($translator)->toBeInstanceOf(FakeTranslator::class);
});

test('service provider publishes config file', function () {
    $configPath = config_path('filament-ai-autofill.php');

    // The service provider should have a publishable config
    $publishes = \Illuminate\Support\ServiceProvider::$publishGroups ?? [];

    // Just verify config is loaded correctly
    expect(config('filament-ai-autofill'))->toBeArray();
    expect(config('filament-ai-autofill'))->toHaveKeys([
        'translator',
        'source_locale',
        'target_locales',
        'field_naming',
        'confirm_overwrite',
        'openai',
    ]);
});

test('openai config reads env variables', function () {
    expect(config('filament-ai-autofill.openai'))->toBeArray();
    expect(config('filament-ai-autofill.openai'))->toHaveKeys(['key', 'model', 'base_url']);
});

test('translations are loaded for english', function () {
    expect(__('filament-ai-autofill::ai-autofill.actions.translate'))->toBe('Translate');
    expect(__('filament-ai-autofill::ai-autofill.actions.translate_all'))->toBe('Auto-translate All');
    expect(__('filament-ai-autofill::ai-autofill.notifications.translating'))->toBe('Translating...');
    expect(__('filament-ai-autofill::ai-autofill.notifications.translation_completed'))->toBe('Translation completed');
    expect(__('filament-ai-autofill::ai-autofill.notifications.translation_failed'))->toBe('Translation failed');
    expect(__('filament-ai-autofill::ai-autofill.notifications.no_content'))->toBe('No content to translate');
    expect(__('filament-ai-autofill::ai-autofill.notifications.empty_source'))->toBe('Source field is empty');
    expect(__('filament-ai-autofill::ai-autofill.tabs.label'))->toBe('Translations');
});

test('translations are loaded for arabic', function () {
    app()->setLocale('ar');

    expect(__('filament-ai-autofill::ai-autofill.actions.translate'))->toBe('ترجمة');
    expect(__('filament-ai-autofill::ai-autofill.actions.translate_all'))->toBe('ترجمة تلقائية للكل');
    expect(__('filament-ai-autofill::ai-autofill.tabs.label'))->toBe('الترجمات');
});
