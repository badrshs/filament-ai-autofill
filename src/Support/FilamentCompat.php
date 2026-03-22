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
