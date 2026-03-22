<?php

use Badrsh\FilamentAiAutofill\Translators\OpenAiTranslator;
use Illuminate\Support\Facades\Http;

test('openai translator throws when api key is missing', function () {
    config([
        'filament-ai-autofill.translator' => OpenAiTranslator::class,
        'filament-ai-autofill.openai.key' => '',
        'services.openai.key' => '',
    ]);

    $translator = new OpenAiTranslator();

    expect(fn () => $translator->translate(['title' => 'مرحبا'], 'ar', ['en']))
        ->toThrow(Exception::class, 'OpenAI API key is missing');
});

test('openai translator returns empty for empty values', function () {
    config(['filament-ai-autofill.openai.key' => 'sk-test']);

    $translator = new OpenAiTranslator();

    $result = $translator->translate([], 'ar', ['en']);

    expect($result)->toBe([]);
});

test('openai translator returns empty for empty target locales', function () {
    config(['filament-ai-autofill.openai.key' => 'sk-test']);

    $translator = new OpenAiTranslator();

    $result = $translator->translate(['title' => 'مرحبا'], 'ar', []);

    expect($result)->toBe([]);
});

test('openai translator makes correct api call and parses response', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        [
                            'text' => json_encode([
                                'title' => ['en' => 'Hello'],
                                'description' => ['en' => 'A description'],
                            ]),
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $translator = new OpenAiTranslator();

    $result = $translator->translate(
        ['title' => 'مرحبا', 'description' => 'وصف'],
        'ar',
        ['en'],
    );

    expect($result)->toBe([
        'title' => ['en' => 'Hello'],
        'description' => ['en' => 'A description'],
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.openai.com/v1/responses'
            && $request->hasHeader('Authorization', 'Bearer sk-test-key')
            && $request['model'] === 'gpt-4o-mini';
    });
});

test('openai translator handles multiple target locales', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        [
                            'text' => json_encode([
                                'title' => ['en' => 'Hello', 'fr' => 'Bonjour'],
                            ]),
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $translator = new OpenAiTranslator();

    $result = $translator->translate(['title' => 'مرحبا'], 'ar', ['en', 'fr']);

    expect($result['title']['en'])->toBe('Hello');
    expect($result['title']['fr'])->toBe('Bonjour');
});

test('openai translator strips markdown code fences from response', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    $wrappedJson = "```json\n" . json_encode(['title' => ['en' => 'Hello']]) . "\n```";

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        [
                            'text' => $wrappedJson,
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $translator = new OpenAiTranslator();
    $result = $translator->translate(['title' => 'مرحبا'], 'ar', ['en']);

    expect($result)->toBe(['title' => ['en' => 'Hello']]);
});

test('openai translator throws on rate limit (429)', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response('Rate limited', 429),
    ]);

    $translator = new OpenAiTranslator();

    expect(fn () => $translator->translate(['title' => 'مرحبا'], 'ar', ['en']))
        ->toThrow(Exception::class, 'rate limit exceeded');
});

test('openai translator throws on server error', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response('Internal Server Error', 500),
    ]);

    $translator = new OpenAiTranslator();

    expect(fn () => $translator->translate(['title' => 'مرحبا'], 'ar', ['en']))
        ->toThrow(Exception::class, 'OpenAI API error (500)');
});

test('openai translator throws on empty response', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        ['text' => null],
                    ],
                ],
            ],
        ], 200),
    ]);

    $translator = new OpenAiTranslator();

    expect(fn () => $translator->translate(['title' => 'مرحبا'], 'ar', ['en']))
        ->toThrow(Exception::class, 'Empty response from OpenAI');
});

test('openai translator throws on invalid json response', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://api.openai.com/v1',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        ['text' => 'This is not JSON at all'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $translator = new OpenAiTranslator();

    expect(fn () => $translator->translate(['title' => 'مرحبا'], 'ar', ['en']))
        ->toThrow(Exception::class, 'Invalid JSON');
});

test('openai translator uses custom base url', function () {
    config([
        'filament-ai-autofill.openai.key' => 'sk-test-key',
        'filament-ai-autofill.openai.model' => 'gpt-4o-mini',
        'filament-ai-autofill.openai.base_url' => 'https://my-proxy.example.com/v1',
    ]);

    Http::fake([
        'my-proxy.example.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        ['text' => json_encode(['title' => ['en' => 'Hello']])],
                    ],
                ],
            ],
        ], 200),
    ]);

    $translator = new OpenAiTranslator();
    $result = $translator->translate(['title' => 'مرحبا'], 'ar', ['en']);

    expect($result)->toBe(['title' => ['en' => 'Hello']]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'my-proxy.example.com');
    });
});
