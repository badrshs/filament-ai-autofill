<?php

namespace Badrsh\FilamentAiAutofill\Tests;

use Badrsh\FilamentAiAutofill\FilamentAiAutofillServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentAiAutofillServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('filament-ai-autofill.translator', \Badrsh\FilamentAiAutofill\Translators\NullTranslator::class);
    }
}
