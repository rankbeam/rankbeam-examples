<?php

namespace App\Support;

use Rankbeam\Seo\Pro\Ai\AiClient;
use Rankbeam\Seo\Pro\Ai\AiResult;

/** Deterministic proposals for browser journeys; no HTTP client or API key. */
class FixtureAiClient extends AiClient
{
    public function complete(string $system, string $prompt, ?array $schema = null, ?int $maxOutputTokens = null, ?string $modelOverride = null): AiResult
    {
        $locale = match (true) {
            str_contains($system, 'Italian') => 'it',
            str_contains($system, 'Turkish') => 'tr',
            str_contains($system, 'Japanese') => 'ja',
            str_contains($system, 'Traditional Chinese') => 'zh_TW',
            str_contains($system, 'Simplified Chinese') => 'zh_CN',
            default => 'en',
        };
        $suggestions = match ($locale) {
            'it' => ['Il caffè a casa, passo dopo passo', 'Come preparare il caffè a casa'],
            'tr' => ['Evde kahve hazırlama rehberi', 'Evde iyi kahve için ilk adımlar'],
            'ja' => ['自宅でおいしいコーヒーを淹れる', '家で楽しむコーヒーの基本'],
            'zh_CN' => ['在家冲泡咖啡的实用指南', '家庭咖啡入门指南'],
            'zh_TW' => ['在家沖泡咖啡的實用指南', '家庭咖啡入門指南'],
            default => ['A practical guide to coffee at home', 'Make better coffee in your kitchen'],
        };
        $record = ['locale' => $locale, 'system' => $system, 'prompt' => $prompt, 'suggestions' => $suggestions];
        if (! is_dir(storage_path('app'))) {
            mkdir(storage_path('app'), 0700, true);
        }
        file_put_contents(storage_path('app/ai-calls.jsonl'), json_encode($record, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND | LOCK_EX);

        return AiResult::success(json_encode(['suggestions' => $suggestions], JSON_UNESCAPED_UNICODE), 0, 0, 'offline-fixture', ['suggestions' => $suggestions]);
    }
}
