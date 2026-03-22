<?php

namespace Molham\FilamentTranslateField\Actions;

use Closure;
use Exception;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Molham\FilamentTranslateField\Contracts\Translator;
use Molham\FilamentTranslateField\Enums\FieldNamingStrategy;
use Molham\FilamentTranslateField\Support\FieldMapper;
use Molham\FilamentTranslateField\Support\TranslationConfig;

/**
 * Batch translation action that translates multiple source fields at once.
 *
 * Place this in an Actions::make([]) block inside your form schema.
 * It collects all source field values, makes ONE API call, and distributes
 * the translations to all target fields.
 *
 * Usage:
 *
 *   // Auto-discover mode (fields detected from schema callback):
 *   TranslateBatchAction::make()
 *
 *   // Explicit mapping mode:
 *   TranslateBatchAction::make()
 *       ->sourceFields(['title', 'description'])
 *       ->targetMapping([
 *           'title' => ['title_en'],
 *           'description' => ['description_en'],
 *       ])
 *
 *   // With schema callback for auto-discovery:
 *   TranslateBatchAction::make()
 *       ->schemaCallback(fn ($locale, $label) => [...fields...])
 */
class TranslateBatchAction extends Action
{
    protected array $sourceFieldNames = [];

    /**
     * Explicit mapping: [sourceField => [locale => targetField]]
     */
    protected array $explicitTargetMapping = [];

    protected ?string $sourceLocale = null;

    protected ?array $targetLocales = null;

    protected ?string $translatorClass = null;

    protected ?bool $confirmOverwrite = null;

    protected ?FieldNamingStrategy $fieldNaming = null;

    /**
     * Schema callback used for auto-discovery of fields across tabs.
     * Signature: fn (string $locale, string $label): array
     */
    protected ?Closure $schemaCallback = null;

    public static function getDefaultName(): ?string
    {
        return 'translate_batch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (): string => __('filament-translate-field::translate-field.actions.translate_all'))
            ->icon('heroicon-m-sparkles')
            ->color('primary')
            ->action(function (Get $get, Set $set): void {
                $this->handleBatchTranslation($get, $set);
            });
    }

    /**
     * Set source field names explicitly.
     *
     * @param  array<int, string>  $fields
     */
    public function sourceFields(array $fields): static
    {
        $this->sourceFieldNames = $fields;

        return $this;
    }

    /**
     * Set explicit target mapping.
     *
     * @param  array<string, array<string, string>>  $mapping  [sourceField => [locale => targetField]]
     *   or [sourceField => [targetField1, targetField2, ...]]
     */
    public function targetMapping(array $mapping): static
    {
        $this->explicitTargetMapping = $mapping;

        return $this;
    }

    /**
     * Set a schema callback for auto-discovery of fields in tab-based forms.
     *
     * The callback receives (string $locale, string $label) and returns
     * an array of Filament form components for that locale's tab.
     */
    public function schemaCallback(Closure $callback): static
    {
        $this->schemaCallback = $callback;

        return $this;
    }

    /**
     * Override the source locale.
     */
    public function sourceLocale(string $locale): static
    {
        $this->sourceLocale = $locale;

        return $this;
    }

    /**
     * Override the target locales.
     *
     * @param  array<int, string>  $locales
     */
    public function targetLocales(array $locales): static
    {
        $this->targetLocales = $locales;

        return $this;
    }

    /**
     * Override the translator class.
     *
     * @param  class-string<Translator>  $class
     */
    public function translator(string $class): static
    {
        $this->translatorClass = $class;

        return $this;
    }

    /**
     * Override the confirm-before-overwrite behavior.
     */
    public function confirmOverwrite(bool $confirm = true): static
    {
        $this->confirmOverwrite = $confirm;

        return $this;
    }

    /**
     * Override the field naming strategy.
     */
    public function fieldNaming(FieldNamingStrategy|string $strategy): static
    {
        if (is_string($strategy)) {
            $strategy = FieldNamingStrategy::from($strategy);
        }

        $this->fieldNaming = $strategy;

        return $this;
    }

    protected function handleBatchTranslation(Get $get, Set $set): void
    {
        try {
            $sourceLocale = $this->resolveSourceLocale();
            $targetLocales = $this->resolveTargetLocales();

            // Resolve the translation config (field mapping)
            $config = $this->resolveTranslationConfig($sourceLocale, $targetLocales);

            if (empty($config->fieldMap)) {
                Notification::make()
                    ->title(__('filament-translate-field::translate-field.notifications.no_content'))
                    ->warning()
                    ->send();

                return;
            }

            // Gather source data
            $dataToTranslate = [];

            foreach ($config->getSourceFields() as $sourceField) {
                $value = $get($sourceField);

                if (filled($value) && is_string($value)) {
                    $dataToTranslate[$sourceField] = $value;
                }
            }

            if (empty($dataToTranslate)) {
                Notification::make()
                    ->title(__('filament-translate-field::translate-field.notifications.no_content'))
                    ->warning()
                    ->send();

                return;
            }

            // Check for existing target content
            if ($this->shouldConfirmOverwrite()) {
                $fieldsWithContent = [];

                foreach ($dataToTranslate as $sourceField => $value) {
                    foreach ($targetLocales as $locale) {
                        $targetField = $config->getTargetField($sourceField, $locale);

                        if ($targetField !== null) {
                            $existingValue = $get($targetField);

                            if (filled($existingValue)) {
                                $fieldsWithContent[] = $targetField;
                            }
                        }
                    }
                }

                // If fields have content, the action's requiresConfirmation()
                // modal will have already shown. We proceed with overwrite.
                // Users can cancel via the modal.
            }

            Notification::make()
                ->title(__('filament-translate-field::translate-field.notifications.translating'))
                ->info()
                ->send();

            // Call translator (single API call for all fields)
            $translator = $this->resolveTranslator();
            $translations = $translator->translate($dataToTranslate, $sourceLocale, $targetLocales);

            // Distribute translations to target fields
            $translatedCount = 0;

            foreach ($translations as $sourceField => $localeTranslations) {
                foreach ($localeTranslations as $locale => $translatedValue) {
                    $targetField = $config->getTargetField($sourceField, $locale);

                    if ($targetField !== null && is_string($translatedValue)) {
                        $set($targetField, $translatedValue);
                        $translatedCount++;
                    }
                }
            }

            Notification::make()
                ->title(__('filament-translate-field::translate-field.notifications.translation_completed_count', [
                    'count' => $translatedCount,
                ]))
                ->success()
                ->send();

        } catch (Exception $e) {
            Log::error('FilamentTranslateField batch: ' . $e->getMessage());

            Notification::make()
                ->title(__('filament-translate-field::translate-field.notifications.translation_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Resolve field mapping either from explicit config or auto-discovery.
     */
    protected function resolveTranslationConfig(string $sourceLocale, array $targetLocales): TranslationConfig
    {
        // 1. Explicit mapping takes priority
        if (! empty($this->explicitTargetMapping)) {
            return $this->buildFromExplicitMapping($sourceLocale, $targetLocales);
        }

        // 2. Schema callback for auto-discovery (tab-based forms)
        if ($this->schemaCallback !== null) {
            return $this->buildFromSchemaCallback($sourceLocale, $targetLocales);
        }

        // 3. Auto-map from source field names using naming strategy
        if (! empty($this->sourceFieldNames)) {
            $strategy = $this->fieldNaming
                ?? FieldNamingStrategy::tryFrom(config('filament-translate-field.field_naming', 'auto'))
                ?? FieldNamingStrategy::AutoDetect;

            return FieldMapper::forFields($this->sourceFieldNames, $sourceLocale, $targetLocales, $strategy);
        }

        return new TranslationConfig($sourceLocale, $targetLocales, []);
    }

    /**
     * Build config from explicit [sourceField => [locale => targetField]] mapping.
     */
    protected function buildFromExplicitMapping(string $sourceLocale, array $targetLocales): TranslationConfig
    {
        $fieldMap = [];

        foreach ($this->explicitTargetMapping as $sourceField => $targets) {
            if (empty($targets)) {
                continue;
            }

            $firstKey = array_key_first($targets);

            if (is_string($firstKey)) {
                // Already keyed by locale: ['en' => 'title_en']
                $fieldMap[$sourceField] = $targets;
            } else {
                // Indexed array: ['title_en', 'title_fr'] — map by locale order
                foreach ($targets as $index => $targetField) {
                    $locale = $targetLocales[$index] ?? null;

                    if ($locale !== null) {
                        $fieldMap[$sourceField][$locale] = $targetField;
                    }
                }
            }
        }

        return new TranslationConfig($sourceLocale, $targetLocales, $fieldMap);
    }

    /**
     * Build config by invoking the schema callback for each locale and mapping
     * fields by their position (index) — matching the existing TranslatableTabs pattern.
     */
    protected function buildFromSchemaCallback(string $sourceLocale, array $targetLocales): TranslationConfig
    {
        $sourceComponents = ($this->schemaCallback)($sourceLocale, strtoupper($sourceLocale));
        $sourceFieldNames = static::extractFieldNames($sourceComponents);

        $fieldMap = [];

        foreach ($targetLocales as $targetLocale) {
            $targetComponents = ($this->schemaCallback)($targetLocale, strtoupper($targetLocale));
            $targetFieldNames = static::extractFieldNames($targetComponents);

            foreach ($sourceFieldNames as $index => $sourceName) {
                if (isset($targetFieldNames[$index])) {
                    $fieldMap[$sourceName][$targetLocale] = $targetFieldNames[$index];
                }
            }
        }

        return new TranslationConfig($sourceLocale, $targetLocales, $fieldMap);
    }

    /**
     * Recursively extract field names from Filament form components.
     *
     * @return array<int, string>
     */
    public static function extractFieldNames(array $components): array
    {
        $names = [];

        foreach ($components as $component) {
            if (method_exists($component, 'getName')) {
                $name = $component->getName();

                if ($name !== null) {
                    $names[] = $name;
                }
            }

            // Filament v3: getChildComponents()
            if (method_exists($component, 'getChildComponents')) {
                try {
                    $children = $component->getChildComponents();

                    if (is_array($children)) {
                        $names = array_merge($names, static::extractFieldNames($children));
                    }
                } catch (\Throwable) {
                    // getChildComponents may require a mounted container in some versions
                }
            }

            // Filament v5: getDefaultChildComponents()
            if (method_exists($component, 'getDefaultChildComponents')) {
                try {
                    $children = $component->getDefaultChildComponents();

                    if (is_array($children)) {
                        $names = array_merge($names, static::extractFieldNames($children));
                    }
                } catch (\Throwable) {
                    // Silently skip if not available
                }
            }
        }

        return $names;
    }

    protected function resolveSourceLocale(): string
    {
        return $this->sourceLocale
            ?? config('filament-translate-field.source_locale', 'ar');
    }

    /**
     * @return array<int, string>
     */
    protected function resolveTargetLocales(): array
    {
        return $this->targetLocales
            ?? config('filament-translate-field.target_locales', ['en']);
    }

    protected function resolveTranslator(): Translator
    {
        $class = $this->translatorClass
            ?? config('filament-translate-field.translator');

        return app($class);
    }

    protected function shouldConfirmOverwrite(): bool
    {
        return $this->confirmOverwrite
            ?? config('filament-translate-field.confirm_overwrite', true);
    }
}
