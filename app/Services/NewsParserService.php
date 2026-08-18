<?php

namespace App\Services;

use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NewsParserService
{

    protected array $sources = [
        [
            'name' => 'РИА Новости',
            'url' => 'https://ria.ru/export/rss2/index.xml',
        ],
        [
            'name' => 'ТАСС',
            'url' => 'https://tass.ru/rss/v2.xml',
        ],
        [
            'name' => 'Ведомости',
            'url' => 'https://www.vedomosti.ru/rss/news',
        ],
        [
            'name' => 'Lenta.ru',
            'url' => 'https://lenta.ru/rss',
        ],
    ];

    // Парсинг новостей из всех источников и сохранение в БД
    public function fetchAll(): int
    {
        $count = 0;

        foreach ($this->sources as $source) {
            try {
                $news = $this->fetchFromRss($source['url'], $source['name']);
                $count += $this->saveNews($news);
            } catch (\Exception $e) {
                Log::error("Error parsing {$source['name']}: " . $e->getMessage());
            }
        }

        return $count;
    }

    // Парсинг одного RSS-источника
    public function fetchFromRss(string $url, string $sourceName): array
    {
        $response = Http::timeout(10)
            ->withOptions([
                'verify' => false,
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch RSS: {$url}");
        }

        $xml = simplexml_load_string($response->body());

        if (!$xml) {
            throw new \Exception("Invalid XML from: {$url}");
        }

        $items = [];
        $count = 0;

        foreach ($xml->channel->item as $rssItem) {
            if ($count >= 10) {
                break;
            }

            $title = (string) $rssItem->title;

            $content = $this->extractContent($rssItem);

            if (empty($content) || $content === $title) {
                $content = $this->generateDescription($title, $sourceName);
            }

            $items[] = [
                'title' => $title,
                'content' => $content,
                'source_url' => (string) $rssItem->link,
                'source_name' => $sourceName,
                'published_at' => $this->parseDate((string) $rssItem->pubDate),
            ];

            $count++;
        }

        return $items;
    }

    /**
     * Извлекает контент из RSS
     */
    protected function extractContent($item): string
    {
        if (isset($item->children('http://purl.org/rss/1.0/modules/content/')->encoded)) {
            $content = (string) $item->children('http://purl.org/rss/1.0/modules/content/')->encoded;
            if (!empty(trim($content))) {
                $content = strip_tags($content);
                $content = mb_substr($content, 0, 500);
                return $content;
            }
        }

        if (isset($item->description) && !empty(trim((string) $item->description))) {
            $content = (string) $item->description;
            $content = strip_tags($content);
            $content = mb_substr($content, 0, 500);
            return $content;
        }

        if (isset($item->title) && !empty(trim((string) $item->title))) {
            $title = (string) $item->title;
            if (mb_strlen($title) > 500) {
                $title = mb_substr($title, 0, 500);
            }
            return $title;
        }

        return '';
    }

    /**
     * Генерирует описание из заголовка, если контент отсутствует
     */
    protected function generateDescription(string $title, string $sourceName): string
    {
        $cleanTitle = str_replace([$sourceName, ' - '], '', $title);
        $description = "Новость от " . $sourceName . ": " . $cleanTitle;

        if (mb_strlen($description) > 500) {
            $description = mb_substr($description, 0, 500) . '...';
        }

        return $description;
    }

    /**
     * Парсит дату из RSS
     */
    protected function parseDate(string $dateString): ?Carbon
    {
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }

    /**
     * Сохраняет новости в БД
     */
    protected function saveNews(array $newsItems): int
    {
        $saved = 0;

        foreach ($newsItems as $item) {
            $exists = News::where('source_url', $item['source_url'])->exists();

            if (!$exists) {
                News::create($item);
                $saved++;
            }
        }

        return $saved;
    }

}