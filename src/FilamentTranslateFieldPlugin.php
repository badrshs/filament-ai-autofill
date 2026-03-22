<?php

namespace Molham\FilamentTranslateField;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentTranslateFieldPlugin implements Plugin
{
    protected ?string $sourceLocale = null;

    /** @var array<int, string>|null */
    protected ?array $targetLocales = null;

    /** @var class-string|null */
    protected ?string $translator = null;

    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'filament-translate-field';
    }

    /**
     * Override the source locale at the panel level.
     */
    public function sourceLocale(string $locale): static
    {
        $this->sourceLocale = $locale;

        return $this;
    }

    /**
     * Override the target locales at the panel level.
     *
     * @param  array<int, string>  $locales
     */
    public function targetLocales(array $locales): static
    {
        $this->targetLocales = $locales;

        return $this;
    }

    /**
     * Override the translator class at the panel level.
     *
     * @param  class-string  $class
     */
    public function translator(string $class): static
    {
        $this->translator = $class;

        return $this;
    }

    public function register(Panel $panel): void
    {
        // Apply panel-level overrides to config at registration time
    }

    public function boot(Panel $panel): void
    {
        if ($this->sourceLocale !== null) {
            config()->set('filament-translate-field.source_locale', $this->sourceLocale);
        }

        if ($this->targetLocales !== null) {
            config()->set('filament-translate-field.target_locales', $this->targetLocales);
        }

        if ($this->translator !== null) {
            config()->set('filament-translate-field.translator', $this->translator);
        }
    }
}
