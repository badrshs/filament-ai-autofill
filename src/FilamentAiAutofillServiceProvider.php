<?php

namespace Badrsh\FilamentAiAutofill;

use Badrsh\FilamentAiAutofill\Contracts\Translator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAiAutofillServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-ai-autofill';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->bind(Translator::class, function ($app) {
            $class = config('filament-ai-autofill.translator');

            // Auto-fallback: if LaravelAiTranslator is configured but laravel/ai
            // is not installed, fall back to OpenAiTranslator automatically.
            if ($class === Translators\LaravelAiTranslator::class && ! function_exists('Laravel\Ai\agent')) {
                $class = Translators\OpenAiTranslator::class;
            }

            return $app->make($class);
        });
    }
}
