<?php

namespace App\Console\Commands;

use App\Services\CategoryScraperService;
use Illuminate\Console\Command;

class ScrapeTvCategory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:tv-category {url=https://www.shoptok.si/tv-prijamnici/cene/56}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape TV category and all subcategories from shoptok.si';

    /**
     * Execute the console command.
     */
    public function handle(CategoryScraperService $scraperService): int
    {
        $url = $this->argument('url');

        $this->info("Starting to scrape TV category from: {$url}");

        try {
            $count = $scraperService->scrapeCategory($url);
            $this->info("Successfully scraped {$count} products from category and subcategories.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error scraping category: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
