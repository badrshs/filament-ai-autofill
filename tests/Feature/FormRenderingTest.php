<?php

/**
 * Feature tests verifying all three usage approaches produce valid
 * Filament schema objects without TypeError or other exceptions.
 *
 * Approach 1: TranslatableTabs (via HasTranslatableFields trait)
 * Approach 2: TranslateFieldAction attached to individual fields
 * Approach 3: TranslateBatchAction inside Actions::make([])
 *
 * Note: Schema components created outside a Livewire context don't have a
 * container, so getChildComponents() cannot be called. We use reflection to
 * inspect the stored childComponents arrays instead.
 */

use Badrsh\FilamentAiAutofill\Actions\TranslateBatchAction;
use Badrsh\FilamentAiAutofill\Actions\TranslateFieldAction;
use Badrsh\FilamentAiAutofill\Concerns\HasTranslatableFields;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

// ── Stub that uses the HasTranslatableFields trait ──────────────

class FormRenderingStub
{
    use HasTranslatableFields;
}

/**
 * Read the raw childComponents stored on a component via reflection.
 * Returns the array stored under the 'default' key.
 */
function getStoredChildComponents(object $component): array
{
    $ref = new ReflectionProperty($component, 'childComponents');
    $ref->setAccessible(true);
    $stored = $ref->getValue($component);

    return $stored['default'] ?? $stored;
}

// ═══════════════════════════════════════════════════════════════════
// Approach 1 — TranslatableTabs
// ═══════════════════════════════════════════════════════════════════

test('translatableTabs builds a valid Tabs component with suffix naming', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}")->label("Title ({$locale})"),
            Textarea::make("description{$suffix}")->label("Description ({$locale})"),
        ],
    );

    expect($tabs)->toBeInstanceOf(Tabs::class);

    $childTabs = getStoredChildComponents($tabs);
    expect($childTabs)->toBeArray()->toHaveCount(2);
    expect($childTabs[0])->toBeInstanceOf(Tab::class);
    expect($childTabs[1])->toBeInstanceOf(Tab::class);
});

test('translatableTabs builds with dot naming strategy', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'dot',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}")->label("Title ({$locale})"),
            Textarea::make("description{$suffix}")->label("Description ({$locale})"),
        ],
    );

    expect($tabs)->toBeInstanceOf(Tabs::class);

    $childTabs = getStoredChildComponents($tabs);
    expect($childTabs)->toBeArray()->toHaveCount(2);
});

test('translatableTabs builds with multiple target locales', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en', 'fr', 'de'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}"),
        ],
    );

    expect($tabs)->toBeInstanceOf(Tabs::class);

    // ar + en + fr + de = 4 tabs
    $childTabs = getStoredChildComponents($tabs);
    expect($childTabs)->toBeArray()->toHaveCount(4);
});

test('translatableTabs auto-detects batch action for multiple fields', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}"),
            Textarea::make("body{$suffix}"),
        ],
    );

    // Get the source tab (first tab = ar)
    $childTabs = getStoredChildComponents($tabs);
    $arTab = $childTabs[0];

    // The source tab's schema should have: title, body, Actions (batch)
    $arSchema = getStoredChildComponents($arTab);
    expect($arSchema)->toBeArray()->toHaveCount(3);

    $lastComponent = end($arSchema);
    expect($lastComponent)->toBeInstanceOf(Actions::class);
});

test('translatableTabs auto-detects field actions for single field', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}"),
        ],
    );

    // Source tab should have just the TextInput (with attached suffix action)
    $childTabs = getStoredChildComponents($tabs);
    $arTab = $childTabs[0];
    $arSchema = getStoredChildComponents($arTab);

    expect($arSchema)->toBeArray()->toHaveCount(1);
    expect($arSchema[0])->toBeInstanceOf(TextInput::class);
});

test('translatableTabs with explicit withBatchAction true', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}"),
        ],
        withBatchAction: true,
    );

    $childTabs = getStoredChildComponents($tabs);
    $arTab = $childTabs[0];
    $arSchema = getStoredChildComponents($arTab);

    // field + Actions wrapper
    expect($arSchema)->toBeArray()->toHaveCount(2);
    expect(end($arSchema))->toBeInstanceOf(Actions::class);
});

test('translatableTabs with explicit withFieldActions true', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}"),
            Textarea::make("body{$suffix}"),
        ],
        withFieldActions: true,
    );

    $childTabs = getStoredChildComponents($tabs);
    $arTab = $childTabs[0];
    $arSchema = getStoredChildComponents($arTab);

    // 2 fields, no batch action since withFieldActions=true auto-sets withBatchAction=false
    expect($arSchema)->toBeArray()->toHaveCount(2);
});

test('translatableTabs target tab has no actions attached', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}"),
            Textarea::make("body{$suffix}"),
        ],
    );

    // Target tab (en = second tab)
    $childTabs = getStoredChildComponents($tabs);
    $enTab = $childTabs[1];
    $enSchema = getStoredChildComponents($enTab);

    // Only the 2 fields, no Actions container
    expect($enSchema)->toBeArray()->toHaveCount(2);
});

// ═══════════════════════════════════════════════════════════════════
// Approach 2 — TranslateFieldAction (suffixAction / hintAction)
// ═══════════════════════════════════════════════════════════════════

test('TranslateFieldAction attaches to TextInput via suffixAction without error', function () {
    $action = TranslateFieldAction::make('translate_title')
        ->sourceField('title')
        ->targetFields(['en' => 'title_en', 'fr' => 'title_fr'])
        ->sourceLocale('ar')
        ->targetLocales(['en', 'fr']);

    $field = TextInput::make('title')
        ->suffixAction($action);

    expect($field)->toBeInstanceOf(TextInput::class);
    expect($field->getName())->toBe('title');
});

test('TranslateFieldAction attaches to Textarea via hintAction without error', function () {
    $action = TranslateFieldAction::make('translate_description')
        ->sourceField('description')
        ->targetFields(['en' => 'description_en'])
        ->sourceLocale('ar')
        ->targetLocales(['en']);

    $field = Textarea::make('description')
        ->hintAction($action);

    expect($field)->toBeInstanceOf(Textarea::class);
    expect($field->getName())->toBe('description');
});

test('attachFieldActions adds suffixAction to TextInput', function () {
    $schema = [TextInput::make('title')];

    $result = (new \ReflectionMethod(FormRenderingStub::class, 'attachFieldActions'))
        ->invoke(null, $schema, 'ar', ['en'], 'suffix');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(TextInput::class);
});

test('attachFieldActions adds hintAction to Textarea (no suffixAction available)', function () {
    $schema = [Textarea::make('description')];

    $result = (new \ReflectionMethod(FormRenderingStub::class, 'attachFieldActions'))
        ->invoke(null, $schema, 'ar', ['en'], 'suffix');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(Textarea::class);
});

test('attachFieldActions handles mixed TextInput and Textarea', function () {
    $schema = [
        TextInput::make('title'),
        Textarea::make('body'),
        TextInput::make('slug'),
    ];

    $result = (new \ReflectionMethod(FormRenderingStub::class, 'attachFieldActions'))
        ->invoke(null, $schema, 'ar', ['en', 'fr'], 'suffix');

    expect($result)->toHaveCount(3);
    expect($result[0])->toBeInstanceOf(TextInput::class);
    expect($result[1])->toBeInstanceOf(Textarea::class);
    expect($result[2])->toBeInstanceOf(TextInput::class);
});

test('attachFieldActions with dot naming builds correct target fields', function () {
    $schema = [TextInput::make('title.ar')];

    $result = (new \ReflectionMethod(FormRenderingStub::class, 'attachFieldActions'))
        ->invoke(null, $schema, 'ar', ['en'], 'dot');

    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(TextInput::class);
});

// ═══════════════════════════════════════════════════════════════════
// Approach 3 — TranslateBatchAction inside Actions::make([])
// ═══════════════════════════════════════════════════════════════════

test('TranslateBatchAction can be created inside Actions::make without error', function () {
    $actions = Actions::make([
        TranslateBatchAction::make()
            ->sourceFields(['title', 'description'])
            ->targetMapping([
                'title' => ['en' => 'title_en'],
                'description' => ['en' => 'description_en'],
            ]),
    ]);

    expect($actions)->toBeInstanceOf(Actions::class);
});

test('TranslateBatchAction with schemaCallback creates without error', function () {
    $actions = Actions::make([
        TranslateBatchAction::make()
            ->schemaCallback(fn (string $locale, string $suffix) => [
                TextInput::make("title{$suffix}"),
                Textarea::make("body{$suffix}"),
            ]),
    ]);

    expect($actions)->toBeInstanceOf(Actions::class);
});

test('TranslateBatchAction with all fluent options creates without error', function () {
    $actions = Actions::make([
        TranslateBatchAction::make()
            ->sourceFields(['title', 'description'])
            ->targetMapping([
                'title' => ['en' => 'title_en', 'fr' => 'title_fr'],
                'description' => ['en' => 'description_en', 'fr' => 'description_fr'],
            ])
            ->sourceLocale('ar')
            ->targetLocales(['en', 'fr'])
            ->translator(\Badrsh\FilamentAiAutofill\Translators\NullTranslator::class)
            ->confirmOverwrite(true),
    ]);

    expect($actions)->toBeInstanceOf(Actions::class);
});

// ═══════════════════════════════════════════════════════════════════
// Full integration — combining all approaches in one schema array
// ═══════════════════════════════════════════════════════════════════

test('full schema with TranslatableTabs, field actions, and standalone batch action builds correctly', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $schema = [
        // 1. Translatable tabs (auto-detection)
        FormRenderingStub::translatableTabs(
            fn (string $locale, string $suffix) => [
                TextInput::make("title{$suffix}"),
                Textarea::make("description{$suffix}"),
            ],
        ),

        // 2. Standalone field with inline action
        TextInput::make('meta_title')
            ->suffixAction(
                TranslateFieldAction::make('translate_meta')
                    ->sourceField('meta_title')
                    ->targetFields(['en' => 'meta_title_en']),
            ),

        // 3. Standalone batch action
        Actions::make([
            TranslateBatchAction::make()
                ->sourceFields(['seo_title', 'seo_desc'])
                ->targetMapping([
                    'seo_title' => ['en' => 'seo_title_en'],
                    'seo_desc' => ['en' => 'seo_desc_en'],
                ]),
        ]),
    ];

    expect($schema)->toHaveCount(3);
    expect($schema[0])->toBeInstanceOf(Tabs::class);
    expect($schema[1])->toBeInstanceOf(TextInput::class);
    expect($schema[2])->toBeInstanceOf(Actions::class);
});

test('full schema with dot naming builds without TypeError', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en', 'fr'],
        'filament-ai-autofill.field_naming' => 'dot',
    ]);

    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            TextInput::make("title{$suffix}"),
            Textarea::make("body{$suffix}"),
            TextInput::make("slug{$suffix}"),
        ],
    );

    expect($tabs)->toBeInstanceOf(Tabs::class);

    // ar + en + fr = 3 tabs
    $childTabs = getStoredChildComponents($tabs);
    expect($childTabs)->toBeArray()->toHaveCount(3);
});

test('Textarea hintAction fallback does not cause TypeError in translatableTabs', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    // Single Textarea field — should use hintAction, NOT suffixAction
    $tabs = FormRenderingStub::translatableTabs(
        fn (string $locale, string $suffix) => [
            Textarea::make("description{$suffix}"),
        ],
        withFieldActions: true,
    );

    expect($tabs)->toBeInstanceOf(Tabs::class);

    $childTabs = getStoredChildComponents($tabs);
    $arTab = $childTabs[0];
    $arSchema = getStoredChildComponents($arTab);

    expect($arSchema)->toBeArray()->toHaveCount(1);
    expect($arSchema[0])->toBeInstanceOf(Textarea::class);
});
