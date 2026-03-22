<?php

namespace Badrsh\FilamentAiAutofill\Translators;

use Exception;
use Illuminate\Support\Facades\Http;
use Badrsh\FilamentAiAutofill\Contracts\Translator;

class OpenAiTranslator implements Translator
{
    protected string $apiKey;

    protected string $model;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('filament-ai-autofill.openai.key', config('services.openai.key', ''));
        $this->model = config('filament-ai-autofill.openai.model', 'gpt-4o-mini');
        $this->baseUrl = config('filament-ai-autofill.openai.base_url', 'https://api.openai.com/v1');
    }

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

        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key is missing. Set OPENAI_API_KEY in your .env file or configure filament-ai-autofill.openai.key.');
        }

        return $this->callApi($values, $sourceLocale, $targetLocales);
    }

    protected function callApi(array $values, string $sourceLocale, array $targetLocales): array
    {
        $targetList = implode(', ', $targetLocales);
        $inputJson = json_encode($values, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
        You are a professional translator. Translate the following JSON object from '{$sourceLocale}' to: {$targetList}.

        CRITICAL RULES:
        - Keep ALL JSON key names EXACTLY as they appear in the input — do NOT rename, shorten, or modify keys in any way
        - Translate for MEANING and NATURAL EXPRESSION, not word-by-word
        - Each translation should sound like a native speaker wrote it
        - Preserve emotional tone and intent
        - Cultural idioms should become natural equivalents in the target language
        - Preserve any HTML tags exactly as-is (do not translate tag names or attributes)

        Input JSON:
        {$inputJson}

        Output ONLY valid JSON in this exact format — no markdown, no explanation:
        {
          "field_name": {
            "locale_code": "translated text"
          }
        }

        Example: if input is {"title.ar": "مرحبا"} and target is en, output must be {"title.ar": {"en": "Hello"}}
        PROMPT;

        $response = Http::withToken($this->apiKey)
            ->timeout((int) config('filament-ai-autofill.openai.timeout', 60))
            ->post("{$this->baseUrl}/responses", [
                'model' => $this->model,
                'input' => [
                    [
                        'role' => 'system',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => 'You are an expert translator. Return only valid JSON. Never wrap in markdown code fences.',
                            ],
                        ],
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();

            if ($status === 429) {
                throw new Exception('Translation rate limit exceeded. Please wait a moment and try again.');
            }

            throw new Exception("OpenAI API error ({$status}): {$body}");
        }

        $content = $response->json('output.0.content.0.text');

        if (! $content) {
            throw new Exception('Empty response from OpenAI.');
        }

        return $this->parseJsonResponse($content);
    }

    /**
     * Parse and clean the JSON response from the AI.
     *
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
