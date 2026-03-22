<?php

namespace Badrsh\FilamentAiTranslate;

use Badrsh\FilamentAiTranslate\Contracts\Translator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentAiTranslateServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-ai-translate';

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
            $class = config('filament-ai-translate.translator');

            return $app->make($class);
        });
    }
}
