<?php

/**
 * Tests for Filament version compatibility, specifically verifying that
 * the action classes satisfy the type constraints of both v3 and v5.
 *
 * In Filament v3, form actions (suffixAction, hintAction, Actions::make)
 * expect Filament\Forms\Components\Actions\Action. In v5, this class was
 * removed and everything was unified under Filament\Actions\Action.
 *
 * The package's FilamentCompat.php aliases the missing classes so both
 * versions work.
 */

use Badrsh\FilamentAiAutofill\Actions\TranslateBatchAction;
use Badrsh\FilamentAiAutofill\Actions\TranslateFieldAction;

// ═══════════════════════════════════════════════════════════════════
// Action base class compatibility
// ═══════════════════════════════════════════════════════════════════

test('FilamentCompat aliases Filament\Forms\Components\Actions\Action on v5', function () {
    // On v5, FilamentCompat.php should have aliased
    // Filament\Actions\Action → Filament\Forms\Components\Actions\Action
    expect(class_exists(\Filament\Forms\Components\Actions\Action::class))->toBeTrue();
});

test('TranslateBatchAction extends Filament\Forms\Components\Actions\Action', function () {
    $action = TranslateBatchAction::make();

    expect($action)->toBeInstanceOf(\Filament\Forms\Components\Actions\Action::class);
});

test('TranslateFieldAction extends Filament\Forms\Components\Actions\Action', function () {
    $action = TranslateFieldAction::make();

    expect($action)->toBeInstanceOf(\Filament\Forms\Components\Actions\Action::class);
});

test('TranslateBatchAction is also instanceof Filament\Actions\Action', function () {
    $action = TranslateBatchAction::make();

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class);
});

test('TranslateFieldAction is also instanceof Filament\Actions\Action', function () {
    $action = TranslateFieldAction::make();

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class);
});

// ═══════════════════════════════════════════════════════════════════
// Type-safe attachment (simulates what v3 type-hints enforce)
// ═══════════════════════════════════════════════════════════════════

test('TranslateFieldAction passes v3 suffixAction type check', function () {
    // In v3, suffixAction() type-hints Filament\Forms\Components\Actions\Action
    $action = TranslateFieldAction::make();

    // This simulates the v3 type check
    $passesTypeCheck = $action instanceof \Filament\Forms\Components\Actions\Action;
    expect($passesTypeCheck)->toBeTrue();

    // And also works with the current v5 suffixAction (Filament\Actions\Action)
    $field = \Filament\Forms\Components\TextInput::make('title')
        ->suffixAction($action);

    expect($field)->toBeInstanceOf(\Filament\Forms\Components\TextInput::class);
});

test('TranslateFieldAction passes v3 hintAction type check', function () {
    // In v3, hintAction() type-hints Filament\Forms\Components\Actions\Action
    $action = TranslateFieldAction::make();

    $passesTypeCheck = $action instanceof \Filament\Forms\Components\Actions\Action;
    expect($passesTypeCheck)->toBeTrue();

    // And also works with the current v5 hintAction (Filament\Actions\Action)
    $field = \Filament\Forms\Components\Textarea::make('body')
        ->hintAction($action);

    expect($field)->toBeInstanceOf(\Filament\Forms\Components\Textarea::class);
});

test('TranslateBatchAction passes v3 Actions::make type check', function () {
    // In v3, Actions::make() expects array<Filament\Forms\Components\Actions\Action>
    $batch = TranslateBatchAction::make();

    $passesTypeCheck = $batch instanceof \Filament\Forms\Components\Actions\Action;
    expect($passesTypeCheck)->toBeTrue();

    // And also works with current v5 container
    $actions = \Filament\Schemas\Components\Actions::make([$batch]);
    expect($actions)->toBeInstanceOf(\Filament\Schemas\Components\Actions::class);
});

// ═══════════════════════════════════════════════════════════════════
// Other FilamentCompat aliases
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
