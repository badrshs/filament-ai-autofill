<?php

namespace Molham\FilamentTranslateField;

use Molham\FilamentTranslateField\Contracts\Translator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentTranslateFieldServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-translate-field';

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
            $class = config('filament-translate-field.translator');

            return $app->make($class);
        });
    }
}
