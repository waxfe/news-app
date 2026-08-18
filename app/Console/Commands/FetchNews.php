<?php

namespace App\Console\Commands;

use App\Services\NewsParserService;
use Illuminate\Console\Command;

class FetchNews extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Fetch news from RSS sources and store in database';

    public function handle(NewsParserService $parser)
    {
        $this->info('Starting news fetch...');

        $count = $parser->fetchAll();

        $this->info("Successfully fetched and saved {$count} news articles.");
    }
}