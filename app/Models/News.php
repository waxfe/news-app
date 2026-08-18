<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'source_url',
        'source_name',
        'published_at',
        'rewritten_versions'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'rewritten_versions' => 'array'
    ];

    public function getRewrittenVersion(string $mood): ?string
    {
        $versions = $this->rewritten_versions ?? [];
        return $versions[$mood] ?? null;
    }

    public function hasRewrittenVersion(string $mood): bool
    {
        $versions = $this->rewritten_versions ?? [];
        return isset($versions[$mood]) && !empty($versions[$mood]);
    }

    public function setRewrittenVersion(string $mood, string $text): void
    {
        $versions = $this->rewritten_versions ?? [];
        $versions[$mood] = $text;
        $this->rewritten_versions = $versions;
        $this->save();
    }
}