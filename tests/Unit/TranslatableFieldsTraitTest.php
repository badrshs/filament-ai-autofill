<?php

use Badrsh\FilamentAiAutofill\Concerns\HasTranslatableFields;

/*
|--------------------------------------------------------------------------
| HasTranslatableFields Trait Tests
|--------------------------------------------------------------------------
|
| Tests for the translatable tabs trait including auto-detection logic,
| buildSuffix method, and schema callback suffix correctness.
|
*/

// Create a concrete class using the trait for testing
class TranslatableFieldsStub
{
    use HasTranslatableFields;
}

test('buildSuffix returns dot notation for all locales when field_naming is dot', function () {
    $method = new ReflectionMethod(TranslatableFieldsStub::class, 'buildSuffix');

    expect($method->invoke(null, 'ar', 'ar', 'dot'))->toBe('.ar');
    expect($method->invoke(null, 'en', 'ar', 'dot'))->toBe('.en');
    expect($method->invoke(null, 'fr', 'ar', 'dot'))->toBe('.fr');
});

test('buildSuffix returns empty string for source locale in suffix mode', function () {
    $method = new ReflectionMethod(TranslatableFieldsStub::class, 'buildSuffix');

    expect($method->invoke(null, 'ar', 'ar', 'suffix'))->toBe('');
    expect($method->invoke(null, 'ar', 'ar', 'auto'))->toBe('');
});

test('buildSuffix returns underscore suffix for target locale in suffix mode', function () {
    $method = new ReflectionMethod(TranslatableFieldsStub::class, 'buildSuffix');

    expect($method->invoke(null, 'en', 'ar', 'suffix'))->toBe('_en');
    expect($method->invoke(null, 'fr', 'ar', 'suffix'))->toBe('_fr');
    expect($method->invoke(null, 'en', 'ar', 'auto'))->toBe('_en');
});

test('auto-detection: single field triggers per-field actions, not batch', function () {
    // This tests the auto-detection logic inline, mirroring HasTranslatableFields
    $sourceLocale = 'ar';
    $fieldNaming = 'suffix';

    $singleFieldCallback = function (string $locale, string $suffix) {
        return [
            new class("title{$suffix}") {
                private string $name;
                public function __construct(string $name) { $this->name = $name; }
                public function getName(): string { return $this->name; }
            },
        ];
    };

    $buildSuffix = new ReflectionMethod(TranslatableFieldsStub::class, 'buildSuffix');
    $sampleSuffix = $buildSuffix->invoke(null, $sourceLocale, $sourceLocale, $fieldNaming);
    $sampleSchema = $singleFieldCallback($sourceLocale, $sampleSuffix);
    $fieldCount = count(array_filter($sampleSchema, fn ($c) => method_exists($c, 'getName') && $c->getName() !== null));

    // Mimic auto-detection: single field → field actions, not batch
    $withFieldActions = $fieldCount <= 1;
    $withBatchAction = $fieldCount > 1;

    expect($fieldCount)->toBe(1);
    expect($withFieldActions)->toBeTrue();
    expect($withBatchAction)->toBeFalse();
});

test('auto-detection: multiple fields triggers batch action, not per-field', function () {
    $sourceLocale = 'ar';
    $fieldNaming = 'suffix';

    $multiFieldCallback = function (string $locale, string $suffix) {
        return [
            new class("title{$suffix}") {
                private string $name;
                public function __construct(string $name) { $this->name = $name; }
                public function getName(): string { return $this->name; }
            },
            new class("body{$suffix}") {
                private string $name;
                public function __construct(string $name) { $this->name = $name; }
                public function getName(): string { return $this->name; }
            },
        ];
    };

    $buildSuffix = new ReflectionMethod(TranslatableFieldsStub::class, 'buildSuffix');
    $sampleSuffix = $buildSuffix->invoke(null, $sourceLocale, $sourceLocale, $fieldNaming);
    $sampleSchema = $multiFieldCallback($sourceLocale, $sampleSuffix);
    $fieldCount = count(array_filter($sampleSchema, fn ($c) => method_exists($c, 'getName') && $c->getName() !== null));

    $withFieldActions = $fieldCount <= 1;
    $withBatchAction = $fieldCount > 1;

    expect($fieldCount)->toBe(2);
    expect($withFieldActions)->toBeFalse();
    expect($withBatchAction)->toBeTrue();
});

test('auto-detection: explicit withBatchAction=false makes withFieldActions=true', function () {
    // When batch is explicitly false, auto-detect should set fieldActions to true
    $withBatchAction = false;
    $withFieldActions = null;

    // Mimic the logic from translatableTabs
    if ($withFieldActions === null && $withBatchAction === null) {
        // This branch doesn't apply
    } elseif ($withFieldActions === null) {
        $withFieldActions = ! $withBatchAction;
    } else {
        $withBatchAction = ! $withFieldActions;
    }

    expect($withFieldActions)->toBeTrue();
    expect($withBatchAction)->toBeFalse();
});

test('auto-detection: explicit withFieldActions=true makes withBatchAction=false', function () {
    $withBatchAction = null;
    $withFieldActions = true;

    if ($withFieldActions === null && $withBatchAction === null) {
        // Not applicable
    } elseif ($withFieldActions === null) {
        $withFieldActions = ! $withBatchAction;
    } else {
        $withBatchAction = ! $withFieldActions;
    }

    expect($withFieldActions)->toBeTrue();
    expect($withBatchAction)->toBeFalse();
});

test('auto-detection: components without getName are not counted as fields', function () {
    $withName = new class {
        public function getName(): string { return 'title'; }
    };

    $withoutName = new class {
        // No getName method — e.g. a layout component
    };

    $withNullName = new class {
        public function getName(): ?string { return null; }
    };

    $sampleSchema = [$withName, $withoutName, $withNullName];
    $fieldCount = count(array_filter($sampleSchema, fn ($c) => method_exists($c, 'getName') && $c->getName() !== null));

    expect($fieldCount)->toBe(1);
});

test('attachFieldActions builds correct target fields for dot notation', function () {
    $method = new ReflectionMethod(TranslatableFieldsStub::class, 'attachFieldActions');

    // Create mock components with getName and suffixAction support
    $component = new class('title.ar') {
        private string $name;
        private ?object $action = null;
        public function __construct(string $name) { $this->name = $name; }
        public function getName(): string { return $this->name; }
        public function suffixAction($action): void { $this->action = $action; }
        public function getAttachedAction(): ?object { return $this->action; }
    };

    $schema = [$component];
    $result = $method->invoke(null, $schema, 'ar', ['en', 'fr'], 'dot');

    // The component should have an action attached
    expect($component->getAttachedAction())->not->toBeNull();
});

test('attachFieldActions skips components without getName', function () {
    $method = new ReflectionMethod(TranslatableFieldsStub::class, 'attachFieldActions');

    $noName = new class {
        // No getName — layout component
    };

    $schema = [$noName];
    $result = $method->invoke(null, $schema, 'ar', ['en'], 'suffix');

    // Should return schema as-is, no crash
    expect($result)->toHaveCount(1);
});

test('attachFieldActions uses hintAction fallback for components without suffixAction', function () {
    $method = new ReflectionMethod(TranslatableFieldsStub::class, 'attachFieldActions');

    // Component with getName and hintAction but NOT suffixAction (like Textarea)
    $component = new class('body.ar') {
        private string $name;
        private ?object $action = null;
        public function __construct(string $name) { $this->name = $name; }
        public function getName(): string { return $this->name; }
        public function hintAction($action): void { $this->action = $action; }
        public function getAttachedAction(): ?object { return $this->action; }
    };

    $schema = [$component];
    $result = $method->invoke(null, $schema, 'ar', ['en'], 'dot');

    // Should have used hintAction fallback
    expect($component->getAttachedAction())->not->toBeNull();
});

test('translatableTabs calls schema callback for each locale', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en', 'fr'],
        'filament-ai-autofill.field_naming' => 'dot',
    ]);

    $calledLocales = [];

    $callback = function (string $locale, string $suffix) use (&$calledLocales) {
        $calledLocales[] = $locale;

        return [
            new class("field{$suffix}") {
                private string $name;
                public function __construct(string $name) { $this->name = $name; }
                public function getName(): string { return $this->name; }
            },
        ];
    };

    TranslatableFieldsStub::translatableTabs($callback, 'Test');

    // Should be called for source + all targets (ar once for auto-detect sample + once for tab, en, fr)
    // The auto-detect also calls the callback once for source locale
    expect($calledLocales)->toContain('ar');
    expect($calledLocales)->toContain('en');
    expect($calledLocales)->toContain('fr');
});

test('suffix notation schema callback receives correct suffixes per locale', function () {
    config([
        'filament-ai-autofill.source_locale' => 'ar',
        'filament-ai-autofill.target_locales' => ['en'],
        'filament-ai-autofill.field_naming' => 'suffix',
    ]);

    $receivedSuffixes = [];

    $callback = function (string $locale, string $suffix) use (&$receivedSuffixes) {
        $receivedSuffixes[$locale] = $suffix;

        return [
            new class("field{$suffix}") {
                private string $name;
                public function __construct(string $name) { $this->name = $name; }
                public function getName(): string { return $this->name; }
            },
        ];
    };

    TranslatableFieldsStub::translatableTabs($callback, 'Test');

    // Source locale ('ar') gets empty suffix, target ('en') gets '_en'
    expect($receivedSuffixes['ar'])->toBe('');
    expect($receivedSuffixes['en'])->toBe('_en');
});
