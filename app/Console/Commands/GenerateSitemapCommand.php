<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'lozan:sitemap';

    protected $description = 'Generate public/sitemap.xml from published products';

    public function handle(SitemapService $sitemap): int
    {
        $count = $sitemap->write(public_path('sitemap.xml'));
        $this->info("Sitemap written with {$count} URLs.");

        return self::SUCCESS;
    }
}
