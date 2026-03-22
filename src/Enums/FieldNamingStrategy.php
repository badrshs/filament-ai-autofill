<?php

namespace Badrsh\FilamentAiAutofill\Enums;

enum FieldNamingStrategy: string
{
    /**
     * Source field has no suffix, targets use locale suffix.
     * Example: title (source/ar) → title_en (target)
     */
    case Suffix = 'suffix';

    /**
     * Fields use dot/arrow notation with locale.
     * Example: title.ar (source) → title.en (target)
     */
    case DotNotation = 'dot';

    /**
     * Auto-detect the naming strategy from field names.
     */
    case AutoDetect = 'auto';
}
