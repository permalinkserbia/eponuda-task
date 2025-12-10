<?php

namespace App\Console\Commands;

use App\Services\TelevisionScraperService;
use Illuminate\Console\Command;

class ScrapeTelevisions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:televisions {url=https://www.shoptok.si/televizorji/cene/206}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape televisions from shoptok.si';

    /**
     * Execute the console command.
     */
    public function handle(TelevisionScraperService $scraperService): int
    {
        $url = $this->argument('url');

        $this->info("Starting to scrape televisions from: {$url}");

        try {
            $count = $scraperService->scrape($url);
            $this->info("Successfully scraped {$count} televisions.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error scraping televisions: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
