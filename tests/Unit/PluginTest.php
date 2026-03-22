<?php

use Badrsh\FilamentAiAutofill\FilamentAiAutofillPlugin;

test('plugin can be created with make()', function () {
    $plugin = FilamentAiAutofillPlugin::make();

    expect($plugin)->toBeInstanceOf(FilamentAiAutofillPlugin::class);
});

test('plugin has correct id', function () {
    $plugin = FilamentAiAutofillPlugin::make();

    expect($plugin->getId())->toBe('filament-ai-autofill');
});

test('plugin source locale is fluent', function () {
    $plugin = FilamentAiAutofillPlugin::make()
        ->sourceLocale('en');

    expect($plugin)->toBeInstanceOf(FilamentAiAutofillPlugin::class);
});

test('plugin target locales is fluent', function () {
    $plugin = FilamentAiAutofillPlugin::make()
        ->targetLocales(['ar', 'fr']);

    expect($plugin)->toBeInstanceOf(FilamentAiAutofillPlugin::class);
});

test('plugin translator is fluent', function () {
    $plugin = FilamentAiAutofillPlugin::make()
        ->translator(\Badrsh\FilamentAiAutofill\Translators\NullTranslator::class);

    expect($plugin)->toBeInstanceOf(FilamentAiAutofillPlugin::class);
});
