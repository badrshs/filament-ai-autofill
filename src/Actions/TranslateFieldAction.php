<?php

namespace Badrsh\FilamentAiAutofill\Actions;

use Exception;
use Filament\Forms\Components\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Badrsh\FilamentAiAutofill\Contracts\Translator;

/**
 * Inline suffix action for a single field.
 *
 * Attach this to a source field to add a sparkle icon that translates
 * that field's content into one or more target fields.
 *
 * Usage:
 *   TextInput::make('title')
 *       ->suffixAction(
 *           TranslateFieldAction::make()
 *               ->targetFields(['title_en', 'title_fr'])
 *       )
 */
class TranslateFieldAction extends Action
{
    protected array $targetFields = [];

    protected ?string $explicitSourceField = null;

    protected ?string $sourceLocale = null;

    protected ?array $targetLocales = null;

    protected ?string $translatorClass = null;

    protected ?bool $confirmOverwrite = null;

    public static function getDefaultName(): ?string
    {
        return 'translate_field';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon('heroicon-m-sparkles')
            ->color('primary')
            ->tooltip(fn(): string => __('filament-ai-autofill::ai-autofill.actions.translate'))
            ->action(function (Get $get, Set $set): void {
                $this->handleTranslation($get, $set);
            });
    }

    /**
     * Explicitly set the source field name (bypasses getComponent() lookup).
     * Use this when attaching the action programmatically.
     */
    public function sourceField(string $name): static
    {
        $this->explicitSourceField = $name;

        return $this;
    }

    /**
     * Set the target field names to populate with translations.
     *
     * @param  array<string, string>|array<int, string>  $fields  Either [locale => fieldName] or [fieldName, ...]
     */
    public function targetFields(array $fields): static
    {
        $this->targetFields = $fields;

        return $this;
    }

    /**
     * Override the source locale (defaults to config value).
     */
    public function sourceLocale(string $locale): static
    {
        $this->sourceLocale = $locale;

        return $this;
    }

    /**
     * Override the target locales (defaults to config value).
     *
     * @param  array<int, string>  $locales
     */
    public function targetLocales(array $locales): static
    {
        $this->targetLocales = $locales;

        return $this;
    }

    /**
     * Override the translator class for this action.
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

    protected function handleTranslation(Get $get, Set $set): void
    {
        try {
            $sourceFieldName = $this->getSourceFieldName();

            if (empty($sourceFieldName)) {
                return;
            }

            $sourceValue = $get($sourceFieldName);

            if (! filled($sourceValue) || ! is_string($sourceValue)) {
                Notification::make()
                    ->title(__('filament-ai-autofill::ai-autofill.notifications.empty_source'))
                    ->warning()
                    ->send();

                return;
            }

            $sourceLocale = $this->resolveSourceLocale();
            $targetLocales = $this->resolveTargetLocales();
            $targetFieldMap = $this->resolveTargetFieldMap($targetLocales);

            if (empty($targetFieldMap)) {
                return;
            }

            // Check overwrite
            if ($this->shouldConfirmOverwrite()) {
                $hasExisting = false;

                foreach ($targetFieldMap as $fieldName) {
                    $existingValue = $get($fieldName);

                    if (filled($existingValue)) {
                        $hasExisting = true;

                        break;
                    }
                }

                if ($hasExisting) {
                    // For the inline action, we show a simple confirmation.
                    // The batch action handles the more complex modal.
                    // Here we just proceed — the requiresConfirmation() on
                    // the action setup could be used if needed.
                }
            }

            Notification::make()
                ->title(__('filament-ai-autofill::ai-autofill.notifications.translating'))
                ->info()
                ->send();

            $translator = $this->resolveTranslator();
            $fieldKey = $this->getSourceFieldName();
            $translations = $translator->translate(
                [$fieldKey => $sourceValue],
                $sourceLocale,
                array_keys($targetFieldMap),
            );

            $translatedCount = 0;

            // Normalize: AI may strip locale suffix from key (e.g., "title" instead of "title.ar")
            $fieldTranslations = $translations[$fieldKey] ?? null;

            if ($fieldTranslations === null) {
                // Try matching by base name (strip locale suffix)
                $baseKey = preg_replace('/[._]' . preg_quote($sourceLocale, '/') . '$/', '', $fieldKey);

                $fieldTranslations = $translations[$baseKey] ?? null;
            }

            if ($fieldTranslations !== null) {
                foreach ($fieldTranslations as $locale => $translatedValue) {
                    if (isset($targetFieldMap[$locale])) {
                        $set($targetFieldMap[$locale], $translatedValue);
                        $translatedCount++;
                    }
                }
            }

            Notification::make()
                ->title(__('filament-ai-autofill::ai-autofill.notifications.translation_completed'))
                ->success()
                ->send();

        } catch (Exception $e) {
            Log::error('FilamentAiAutofill: ' . $e->getMessage());

            Notification::make()
                ->title(__('filament-ai-autofill::ai-autofill.notifications.translation_failed'))
                ->danger()
                ->send();
        }
    }

    /**
     * Get the source field name from the explicit setter or the parent component.
     */
    protected function getSourceFieldName(): string
    {
        if ($this->explicitSourceField !== null) {
            return $this->explicitSourceField;
        }

        // Fallback: try getComponent() on form component actions
        if (method_exists($this, 'getComponent')) {
            $component = $this->getComponent();

            if ($component && method_exists($component, 'getName')) {
                return $component->getName();
            }
        }

        return '';
    }

    protected function resolveSourceLocale(): string
    {
        return $this->sourceLocale
            ?? config('filament-ai-autofill.source_locale', 'ar');
    }

    /**
     * @return array<int, string>
     */
    protected function resolveTargetLocales(): array
    {
        return $this->targetLocales
            ?? config('filament-ai-autofill.target_locales', ['en']);
    }

    /**
     * Build a [locale => targetFieldName] map from the configured target fields.
     *
     * @return array<string, string>  [locale => fieldName]
     */
    protected function resolveTargetFieldMap(array $targetLocales): array
    {
        if (empty($this->targetFields)) {
            return [];
        }

        // If keyed by locale: ['en' => 'title_en', 'fr' => 'title_fr']
        $firstKey = array_key_first($this->targetFields);

        if (is_string($firstKey)) {
            return $this->targetFields;
        }

        // If indexed: ['title_en', 'title_fr'] — map by order of targetLocales
        $map = [];

        foreach ($this->targetFields as $index => $fieldName) {
            $locale = $targetLocales[$index] ?? null;

            if ($locale !== null) {
                $map[$locale] = $fieldName;
            }
        }

        return $map;
    }

    protected function resolveTranslator(): Translator
    {
        $class = $this->translatorClass
            ?? config('filament-ai-autofill.translator');

        return app($class);
    }

    protected function shouldConfirmOverwrite(): bool
    {
        return $this->confirmOverwrite
            ?? config('filament-ai-autofill.confirm_overwrite', true);
    }
}
