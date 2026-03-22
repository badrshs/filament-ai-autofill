<?php

namespace Badrsh\FilamentAiAutofill\Concerns;

use Closure;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Badrsh\FilamentAiAutofill\Actions\TranslateBatchAction;
use Badrsh\FilamentAiAutofill\Actions\TranslateFieldAction;

/**
 * Convenience trait for building translatable tabbed forms with
 * automatic AI translation actions.
 *
 * Usage in a Filament Resource:
 *
 *   use HasTranslatableFields;
 *
 *   public static function form(Form $form): Form
 *   {
 *       return $form->schema([
 *           static::translatableTabs(
 *               fn (string $locale, string $suffix) => [
 *                   TextInput::make("title{$suffix}")
 *                       ->required($locale === 'ar'),
 *                   Textarea::make("description{$suffix}"),
 *               ],
 *           ),
 *       ]);
 *   }
 */
trait HasTranslatableFields
{
    /**
     * Create a Tabs component with one tab per configured locale,
     * automatically attaching translation actions.
     *
     * @param  Closure  $schemaCallback  fn(string $locale, string $suffix): array
     *   Receives the locale code and a suffix string for field naming.
     *   For suffix naming: source locale gets '' suffix, targets get '_en', '_fr', etc.
     *   For dot naming: source gets '.ar', target gets '.en', etc.
     * @param  string  $label  The tabs component label.
     * @param  bool|null  $withBatchAction  Add a batch translate button. Null = auto-detect (true when multiple fields).
     * @param  bool|null  $withFieldActions  Add inline translate icons. Null = auto-detect (true when single field).
     * @param  string|null  $sourceLocale  Override the default source locale.
     * @param  array|null  $targetLocales  Override the default target locales.
     * @param  array|null  $locales  Override the full list of locales (source + targets).
     */
    public static function translatableTabs(
        Closure $schemaCallback,
        string $label = '',
        ?bool $withBatchAction = null,
        ?bool $withFieldActions = null,
        ?string $sourceLocale = null,
        ?array $targetLocales = null,
        ?array $locales = null,
    ): Tabs {
        $sourceLocale = $sourceLocale ?? config('filament-ai-autofill.source_locale', 'ar');
        $targetLocales = $targetLocales ?? config('filament-ai-autofill.target_locales', ['en']);
        $locales = $locales ?? array_unique(array_merge([$sourceLocale], $targetLocales));
        $label = $label ?: __('filament-ai-autofill::ai-autofill.tabs.label');

        $fieldNaming = config('filament-ai-autofill.field_naming', 'auto');

        // Auto-detect action mode when not explicitly set:
        // count fields from the source locale schema callback
        if ($withBatchAction === null || $withFieldActions === null) {
            $sampleSuffix = static::buildSuffix($sourceLocale, $sourceLocale, $fieldNaming);
            $sampleSchema = $schemaCallback($sourceLocale, $sampleSuffix);
            $fieldCount = count(array_filter($sampleSchema, fn($c) => method_exists($c, 'getName') && $c->getName() !== null));

            if ($withFieldActions === null && $withBatchAction === null) {
                // Neither set: single field → sparkle per field, multiple → batch
                $withFieldActions = $fieldCount <= 1;
                $withBatchAction = $fieldCount > 1;
            } elseif ($withFieldActions === null) {
                // Batch explicitly set, auto-detect field actions as the opposite
                $withFieldActions = ! $withBatchAction;
            } else {
                // Field actions explicitly set, auto-detect batch as the opposite
                $withBatchAction = ! $withFieldActions;
            }
        }

        return Tabs::make($label)
            ->tabs(
                array_map(
                    function (string $locale) use (
                        $schemaCallback,
                        $sourceLocale,
                        $targetLocales,
                        $fieldNaming,
                        $withBatchAction,
                        $withFieldActions,
                    ) {
                        $suffix = static::buildSuffix($locale, $sourceLocale, $fieldNaming);
                        $schema = $schemaCallback($locale, $suffix);

                        // Only enhance the source locale tab with actions
                        if ($locale === $sourceLocale) {
                            if ($withFieldActions) {
                                $schema = static::attachFieldActions(
                                    $schema,
                                    $sourceLocale,
                                    $targetLocales,
                                    $fieldNaming,
                                );
                            }

                            if ($withBatchAction) {
                                $schema[] = Actions::make([
                                    TranslateBatchAction::make()
                                        ->schemaCallback(
                                            // Wrap so buildFromSchemaCallback always gets the correct
                                            // suffix (empty, _en, .ar, etc.) regardless of what
                                            // second argument it passes in.
                                            fn (string $locale, string $_ignored) => $schemaCallback(
                                                $locale,
                                                static::buildSuffix($locale, $sourceLocale, $fieldNaming),
                                            )
                                        ),
                                ]);
                            }
                        }

                        return Tab::make(strtoupper($locale))
                            ->schema($schema);
                    },
                    $locales,
                ),
            )
            ->persistTabInQueryString()
            ->columnSpanFull();
    }

    /**
     * Build the field name suffix for a given locale.
     */
    protected static function buildSuffix(string $locale, string $sourceLocale, string $fieldNaming): string
    {
        if ($fieldNaming === 'dot') {
            return ".{$locale}";
        }

        // Suffix mode (or auto): source locale has no suffix, targets have _locale
        if ($locale === $sourceLocale) {
            return '';
        }

        return "_{$locale}";
    }

    /**
     * Walk through schema components and attach TranslateFieldAction
     * to each field that has a name (input fields).
     *
     * @return array The schema with inline actions attached to each field.
     */
    protected static function attachFieldActions(
        array $schema,
        string $sourceLocale,
        array $targetLocales,
        string $fieldNaming,
    ): array {
        foreach ($schema as $component) {
            if (! method_exists($component, 'getName')) {
                continue;
            }

            $hasSuffixAction = method_exists($component, 'suffixAction');
            $hasHintAction = method_exists($component, 'hintAction');

            if (! $hasSuffixAction && ! $hasHintAction) {
                continue;
            }

            $name = $component->getName();

            if ($name === null) {
                continue;
            }

            // Build target field names for this source field
            $targetFields = [];

            foreach ($targetLocales as $locale) {
                if ($fieldNaming === 'dot') {
                    $targetField = str_replace(".{$sourceLocale}", ".{$locale}", $name);
                } else {
                    // Suffix: name → name_locale
                    $baseName = $name;
                    $suffixPattern = "_{$sourceLocale}";

                    if (str_ends_with($name, $suffixPattern)) {
                        $baseName = substr($name, 0, -strlen($suffixPattern));
                    }

                    $targetField = "{$baseName}_{$locale}";
                }

                $targetFields[$locale] = $targetField;
            }

            $action = TranslateFieldAction::make('translate_' . $name)
                ->sourceField($name)
                ->targetFields($targetFields)
                ->sourceLocale($sourceLocale)
                ->targetLocales($targetLocales);

            // Prefer suffixAction (inline icon), fall back to hintAction (top-right)
            if ($hasSuffixAction) {
                $component->suffixAction($action);
            } else {
                $component->hintAction($action);
            }
        }

        return $schema;
    }
}
