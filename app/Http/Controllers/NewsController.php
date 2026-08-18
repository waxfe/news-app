<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Services\NewsRewriterService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::latest('published_at')
            ->take(20)
            ->get();

        return view('news.index', compact('news'));
    }

    public function show(News $news, Request $request, NewsRewriterService $rewriter)
    {
        $mood = $request->input('mood', 'neutral');

        // Валидация mood
        if (!array_key_exists($mood, NewsRewriterService::MOODS)) {
            $mood = 'neutral';
        }

        $rewritten = $rewriter->rewrite($news, $mood);

        return response()->json([
            'success' => true,
            'id' => $news->id,
            'title' => $news->title,
            'original' => $news->content,
            'rewritten' => $rewritten,
            'source_url' => $news->source_url,
            'source_name' => $news->source_name,
            'published_at' => $news->published_at?->format('d.m.Y H:i'),
            'mood' => $mood,
            'mood_label' => NewsRewriterService::MOODS[$mood],
            'is_rewritten' => $rewritten !== $news->content,
        ]);
    }
}