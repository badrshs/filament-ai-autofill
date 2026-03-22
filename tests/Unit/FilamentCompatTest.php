<?php

/**
 * Tests for Filament version compatibility, specifically verifying that
 * the FilamentCompat aliases work correctly for v4/v5.
 *
 * The package uses Filament v5 namespaces internally. FilamentCompat.php
 * aliases the older v4 namespace classes so both versions work.
 */

use Badrsh\FilamentAiAutofill\Actions\TranslateBatchAction;
use Badrsh\FilamentAiAutofill\Actions\TranslateFieldAction;

// ═══════════════════════════════════════════════════════════════════
// Action base class
// ═══════════════════════════════════════════════════════════════════

test('TranslateBatchAction extends Filament\Actions\Action', function () {
    $action = TranslateBatchAction::make();

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class);
});

test('TranslateFieldAction extends Filament\Actions\Action', function () {
    $action = TranslateFieldAction::make();

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class);
});

// ═══════════════════════════════════════════════════════════════════
// Field attachment (v5 native)
// ═══════════════════════════════════════════════════════════════════

test('TranslateFieldAction works with suffixAction', function () {
    $action = TranslateFieldAction::make();

    $field = \Filament\Forms\Components\TextInput::make('title')
        ->suffixAction($action);

    expect($field)->toBeInstanceOf(\Filament\Forms\Components\TextInput::class);
});

test('TranslateFieldAction works with hintAction', function () {
    $action = TranslateFieldAction::make();

    $field = \Filament\Forms\Components\Textarea::make('body')
        ->hintAction($action);

    expect($field)->toBeInstanceOf(\Filament\Forms\Components\Textarea::class);
});

test('TranslateBatchAction works with Actions container', function () {
    $batch = TranslateBatchAction::make();

    $actions = \Filament\Schemas\Components\Actions::make([$batch]);
    expect($actions)->toBeInstanceOf(\Filament\Schemas\Components\Actions::class);
});

// ═══════════════════════════════════════════════════════════════════
// FilamentCompat aliases
// ═══════════════════════════════════════════════════════════════════

test('FilamentCompat aliases Get and Set utilities', function () {
    expect(class_exists(\Filament\Schemas\Components\Utilities\Get::class))->toBeTrue();
    expect(class_exists(\Filament\Schemas\Components\Utilities\Set::class))->toBeTrue();
});

test('FilamentCompat aliases Tabs and Tab', function () {
    expect(class_exists(\Filament\Schemas\Components\Tabs::class))->toBeTrue();
    expect(class_exists(\Filament\Schemas\Components\Tabs\Tab::class))->toBeTrue();
});

test('FilamentCompat aliases Actions container', function () {
    expect(class_exists(\Filament\Schemas\Components\Actions::class))->toBeTrue();
});
