<?php

namespace Badrsh\FilamentAiAutofill\Translators;

use Exception;
use Badrsh\FilamentAiAutofill\Contracts\Translator;

use function Laravel\Ai\agent;

class LaravelAiTranslator implements Translator
{
    /**
     * @param  array<string, string>  $values
     * @param  array<int, string>  $targetLocales
     * @return array<string, array<string, string>>
     */
    public function translate(array $values, string $sourceLocale, array $targetLocales): array
    {
        if (empty($values) || empty($targetLocales)) {
            return [];
        }

        $targetList = implode(', ', $targetLocales);
        $inputJson = json_encode($values, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
        Translate the following JSON object from '{$sourceLocale}' to: {$targetList}.

        TRANSLATION PRINCIPLES:
        - Translate for MEANING and NATURAL EXPRESSION, not word-by-word
        - Each translation should sound like a native speaker wrote it
        - Preserve emotional tone and intent
        - Cultural idioms should become natural equivalents in the target language
        - Rephrase awkward literal translations naturally
        - Preserve any HTML tags exactly as-is (do not translate tag names or attributes)

        Input JSON:
        {$inputJson}

        Output ONLY valid JSON in this exact format — no markdown, no explanation:
        {
          "field_name": {
            "locale_code": "translated text"
          }
        }
        PROMPT;

        $response = agent(
            instructions: 'You are an expert translator. Return only valid JSON. Never wrap in markdown code fences.',
        )->prompt(
            $prompt,
            timeout: (int) config('filament-ai-autofill.laravel_ai.timeout', 60),
        );

        $content = trim((string) $response);

        return $this->parseJsonResponse($content);
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function parseJsonResponse(string $content): array
    {
        $content = trim($content);

        // Strip markdown code fences if present
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```\s*$/', '', $content);
        $content = trim($content);

        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in translation response: ' . json_last_error_msg());
        }

        return $json;
    }
}
