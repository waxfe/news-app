<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NewsRewriterService
{
    public const MOODS = [
        'neutral' => 'нейтрально',
        'joyful' => 'радостно',
        'sad' => 'грустно',
        'ironic' => 'иронично',
        'optimistic' => 'оптимистично',
    ];

    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = env("OPENROUTER_API_KEY");
        $this->apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    }

    public function rewrite(News $news, string $mood): string
    {
        // Проверяем в БД
        $existing = $news->getRewrittenVersion($mood);
        if ($existing !== null && !empty($existing)) {
            return $existing;
        }

        // Проверяем кэш
        $cacheKey = "news_rewrite_{$news->id}_{$mood}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null && !empty($cached)) {
            $news->setRewrittenVersion($mood, $cached);
            return $cached;
        }

        // Генерируем через AI
        $rewritten = $this->callApi($news->content, $mood);

        // Сохраняем результат
        if ($rewritten !== $news->content && !empty($rewritten)) {
            $news->setRewrittenVersion($mood, $rewritten);
            Cache::put($cacheKey, $rewritten, 3600);
            return $rewritten;
        }

        return $news->content;
    }

    protected function callApi(string $originalText, string $mood): string
    {
        $moodName = self::MOODS[$mood] ?? 'нейтрально';
        $prompt = $this->buildPrompt($originalText, $moodName);

        try {
            $response = Http::timeout(60)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => env('APP_URL', 'http://localhost'),
                    'X-Title' => 'News App',
                ])->post($this->apiUrl, [
                        'model' => 'google/gemini-2.5-flash-lite',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Ты — профессиональный редактор новостей. Переписываешь новости в заданном тоне, сохраняя все факты. Отвечай только переписанным текстом.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 500,
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;

                if ($content && !empty(trim($content))) {
                    return trim($content);
                }
            }

            Log::error('API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return $originalText;

        } catch (\Exception $e) {
            Log::error('API exception', ['message' => $e->getMessage()]);
            return $originalText;
        }
    }

    protected function buildPrompt(string $text, string $mood): string
    {
        return "Перепиши следующий новостной текст в {$mood} тоне.
        Сохрани все факты без изменений: имена, даты, числа, названия, цитаты.
        Не добавляй вымышленных фактов. Измени только эмоциональную окраску.
        Ответь только переписанным текстом.

        Текст новости: {$text}

        Переписанный текст:";
    }
}