<?php

use Badrsh\FilamentAiAutofill\Actions\TranslateBatchAction;
use Badrsh\FilamentAiAutofill\Support\TranslationConfig;

test('buildCallbackSuffix returns dot suffix for dot naming', function () {
    $action = TranslateBatchAction::make();

    $method = new ReflectionMethod($action, 'buildCallbackSuffix');

    expect($method->invoke($action, 'ar', 'ar', 'dot'))->toBe('.ar');
    expect($method->invoke($action, 'en', 'ar', 'dot'))->toBe('.en');
    expect($method->invoke($action, 'fr', 'ar', 'dot'))->toBe('.fr');
});

test('buildCallbackSuffix returns empty suffix for source locale in suffix mode', function () {
    $action = TranslateBatchAction::make();

    $method = new ReflectionMethod($action, 'buildCallbackSuffix');

    expect($method->invoke($action, 'ar', 'ar', 'suffix'))->toBe('');
    expect($method->invoke($action, 'ar', 'ar', 'auto'))->toBe('');
});

test('buildCallbackSuffix returns underscore suffix for target locale in suffix mode', function () {
    $action = TranslateBatchAction::make();

    $method = new ReflectionMethod($action, 'buildCallbackSuffix');

    expect($method->invoke($action, 'en', 'ar', 'suffix'))->toBe('_en');
    expect($method->invoke($action, 'fr', 'ar', 'suffix'))->toBe('_fr');
    expect($method->invoke($action, 'en', 'ar', 'auto'))->toBe('_en');
});

test('buildFromSchemaCallback uses correct suffixes for dot notation', function () {
    config(['filament-ai-autofill.field_naming' => 'dot']);

    $action = TranslateBatchAction::make();

    // Create a schema callback that returns field names based on suffix
    $callback = function (string $locale, string $suffix) {
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

    $action->schemaCallback($callback);

    $method = new ReflectionMethod($action, 'buildFromSchemaCallback');
    $config = $method->invoke($action, 'ar', ['en']);

    expect($config)->toBeInstanceOf(TranslationConfig::class);
    expect($config->getSourceFields())->toBe(['title.ar', 'body.ar']);
    expect($config->getTargetField('title.ar', 'en'))->toBe('title.en');
    expect($config->getTargetField('body.ar', 'en'))->toBe('body.en');
});

test('buildFromSchemaCallback uses correct suffixes for suffix naming', function () {
    config(['filament-ai-autofill.field_naming' => 'suffix']);

    $action = TranslateBatchAction::make();

    $callback = function (string $locale, string $suffix) {
        return [
            new class("title{$suffix}") {
                private string $name;
                public function __construct(string $name) { $this->name = $name; }
                public function getName(): string { return $this->name; }
            },
        ];
    };

    $action->schemaCallback($callback);

    $method = new ReflectionMethod($action, 'buildFromSchemaCallback');
    $config = $method->invoke($action, 'ar', ['en', 'fr']);

    expect($config->getSourceFields())->toBe(['title']);
    expect($config->getTargetField('title', 'en'))->toBe('title_en');
    expect($config->getTargetField('title', 'fr'))->toBe('title_fr');
});

test('buildFromSchemaCallback handles multiple target locales with dot notation', function () {
    config(['filament-ai-autofill.field_naming' => 'dot']);

    $action = TranslateBatchAction::make();

    $callback = function (string $locale, string $suffix) {
        return [
            new class("title{$suffix}") {
                private string $name;
                public function __construct(string $name) { $this->name = $name; }
                public function getName(): string { return $this->name; }
            },
        ];
    };

    $action->schemaCallback($callback);

    $method = new ReflectionMethod($action, 'buildFromSchemaCallback');
    $config = $method->invoke($action, 'ar', ['en', 'fr', 'de']);

    expect($config->getTargetField('title.ar', 'en'))->toBe('title.en');
    expect($config->getTargetField('title.ar', 'fr'))->toBe('title.fr');
    expect($config->getTargetField('title.ar', 'de'))->toBe('title.de');
});

test('extractFieldNames handles components with and without getName', function () {
    $withName = new class {
        public function getName(): string { return 'title'; }
    };

    $withoutName = new class {
        // No getName method
    };

    $withNullName = new class {
        public function getName(): ?string { return null; }
    };

    $result = TranslateBatchAction::extractFieldNames([$withName, $withoutName, $withNullName]);

    expect($result)->toBe(['title']);
});
