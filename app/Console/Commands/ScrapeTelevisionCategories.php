<?php

namespace App\Console\Commands;

use App\Services\TelevisionCategoryScraperService;
use Illuminate\Console\Command;

class ScrapeTelevisionCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:television-categories {url=https://www.shoptok.si/tv-prijamnici/cene/56}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape TV categories and products from shoptok.si';

    /**
     * Execute the console command.
     */
    public function handle(TelevisionCategoryScraperService $scraperService): int
    {
        $url = $this->argument('url');

        $this->info("Starting to scrape TV categories from: {$url}");

        try {
            $count = $scraperService->scrapeCategories($url);
            $this->info("Successfully scraped {$count} products from all categories.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error scraping categories: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

