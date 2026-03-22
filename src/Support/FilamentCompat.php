<?php

/**
 * Filament version compatibility layer.
 *
 * This package uses Filament v5 namespaces internally. When running on
 * Filament v3 or v4, this file creates class aliases so the v5 imports
 * resolve to the equivalent v3/v4 classes.
 *
 * Loaded automatically via composer "files" autoload.
 */

// Get/Set utilities
// v3/v4: Filament\Forms\Get / Filament\Forms\Set
// v5:    Filament\Schemas\Components\Utilities\Get / Set
if (! class_exists(\Filament\Schemas\Components\Utilities\Get::class)) {
    if (class_exists(\Filament\Forms\Get::class)) {
        class_alias(\Filament\Forms\Get::class, \Filament\Schemas\Components\Utilities\Get::class);
    }
}

if (! class_exists(\Filament\Schemas\Components\Utilities\Set::class)) {
    if (class_exists(\Filament\Forms\Set::class)) {
        class_alias(\Filament\Forms\Set::class, \Filament\Schemas\Components\Utilities\Set::class);
    }
}

// Tabs components
// v3/v4: Filament\Forms\Components\Tabs / Tab
// v5:    Filament\Schemas\Components\Tabs / Tab
if (! class_exists(\Filament\Schemas\Components\Tabs::class)) {
    if (class_exists(\Filament\Forms\Components\Tabs::class)) {
        class_alias(\Filament\Forms\Components\Tabs::class, \Filament\Schemas\Components\Tabs::class);
    }
}

if (! class_exists(\Filament\Schemas\Components\Tabs\Tab::class)) {
    if (class_exists(\Filament\Forms\Components\Tabs\Tab::class)) {
        class_alias(\Filament\Forms\Components\Tabs\Tab::class, \Filament\Schemas\Components\Tabs\Tab::class);
    }
}

// Actions container component
// v3/v4: Filament\Forms\Components\Actions
// v5:    Filament\Schemas\Components\Actions
if (! class_exists(\Filament\Schemas\Components\Actions::class)) {
    if (class_exists(\Filament\Forms\Components\Actions::class)) {
        class_alias(\Filament\Forms\Components\Actions::class, \Filament\Schemas\Components\Actions::class);
    }
}

// Form-level Action base class
// v3/v4: Filament\Forms\Components\Actions\Action (extends Filament\Actions\Action)
// v5:    Removed — use Filament\Actions\Action directly
//
// The package's action classes extend Filament\Forms\Components\Actions\Action
// so they satisfy the v3 type-hints (suffixAction, hintAction, Actions::make).
// On v5, where this class no longer exists, we alias it back to the unified class.
if (! class_exists(\Filament\Forms\Components\Actions\Action::class)) {
    if (class_exists(\Filament\Actions\Action::class)) {
        class_alias(\Filament\Actions\Action::class, \Filament\Forms\Components\Actions\Action::class);
    }
}
