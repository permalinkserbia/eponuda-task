<?php

namespace App\Services;

use App\Repositories\TelevisionRepositoryInterface;
use App\Repositories\TvCategoryRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class TelevisionCategoryScraperService extends TelevisionScraperService
{
    public function __construct(
        TelevisionRepositoryInterface $televisionRepository,
        private readonly TvCategoryRepositoryInterface $categoryRepository
    ) {
        // Call parent constructor to initialize TelevisionRepositoryInterface
        parent::__construct($televisionRepository);
    }

    /**
     * Scrape categories from the entry URL and scrape products for each category
     */
    public function scrapeCategories(string $entryUrl = 'https://www.shoptok.si/tv-prijamnici/cene/56'): int
    {
        $html = $this->fetchHtml($entryUrl);
        $crawler = new Crawler($html);

        $totalProducts = 0;

        // Find category containers with class "col-4 col-md-3 col-lg-2 col-xl-1-5 mb-5"
        $crawler->filter('[class*="col-4"][class*="col-md-3"][class*="col-lg-2"][class*="col-xl-1-5"][class*="mb-5"]')->each(function (Crawler $categoryNode) use (&$totalProducts, $entryUrl) {
            try {
                // Extract category data
                $categoryData = $this->extractCategoryData($categoryNode, $entryUrl);
                
                if (!$categoryData) {
                    return;
                }

                // Create or update category
                $category = $this->categoryRepository->updateOrCreate(
                    ['url' => $categoryData['url']],
                    [
                        'name' => $categoryData['name'],
                        'slug' => $this->generateSlug($categoryData['url']),
                        'parent_id' => null,
                    ]
                );

                // Use existing TelevisionScraperService method (inherited) to scrape products from this category
                // Pass category ID to associate products with the category
                $productsCount = $this->scrapeForCategory($categoryData['url'], $category->id);
                $totalProducts += $productsCount;
            } catch (\Exception $e) {
                Log::error("Error scraping category: " . $e->getMessage());
            }
        });

        return $totalProducts;
    }

    /**
     * Extract category data (name, URL, image) from a category node
     */
    private function extractCategoryData(Crawler $categoryNode, string $baseUrl): ?array
    {
        // Extract category name
        $name = $this->extractCategoryName($categoryNode);
        if (!$name) {
            return null;
        }

        // Extract category URL from anchor tag
        $url = $this->extractCategoryUrl($categoryNode, $baseUrl);
        if (!$url) {
            return null;
        }

        // Extract category image from picture element
        $image = $this->extractCategoryImage($categoryNode, $baseUrl);

        return [
            'name' => $name,
            'url' => $url,
            'image' => $image,
        ];
    }

    /**
     * Extract category name from the category node
     */
    private function extractCategoryName(Crawler $categoryNode): ?string
    {
        try {
            // Category name is inside: .text-center.line-height-13.mt-3.mb-0.text-16.font-semibold.font-poppins
            $nameElement = $categoryNode->filter('.text-center.line-height-13.mt-3.mb-0.text-16.font-semibold.font-poppins')->first();
            if ($nameElement->count() > 0) {
                return trim($nameElement->text());
            }
        } catch (\Exception $e) {
            // Try with partial class match
            try {
                $nameElement = $categoryNode->filter('[class*="text-center"][class*="line-height-13"][class*="font-semibold"]')->first();
                if ($nameElement->count() > 0) {
                    return trim($nameElement->text());
                }
            } catch (\Exception $e2) {
                // Category name not found
            }
        }

        return null;
    }

    /**
     * Extract category URL from anchor tag
     */
    private function extractCategoryUrl(Crawler $categoryNode, string $baseUrl): ?string
    {
        try {
            // Find anchor tag within the category node
            $link = $categoryNode->filter('a')->first();
            if ($link->count() > 0) {
                $href = $link->attr('href');
                if ($href) {
                    return $this->makeAbsoluteUrl($href, $baseUrl);
                }
            }
        } catch (\Exception $e) {
            // Try to find link in ancestors
            try {
                $link = $categoryNode->ancestors('a')->first();
                if ($link->count() > 0) {
                    $href = $link->attr('href');
                    if ($href) {
                        return $this->makeAbsoluteUrl($href, $baseUrl);
                    }
                }
            } catch (\Exception $e2) {
                // Category URL not found
            }
        }

        return null;
    }

    /**
     * Extract category image from picture element
     */
    private function extractCategoryImage(Crawler $categoryNode, string $baseUrl): ?string
    {
        try {
            // First, try to find picture element
            $picture = $categoryNode->filter('picture')->first();
            if ($picture->count() > 0) {
                // Try to get image from source element (preferred)
                $source = $picture->filter('source')->first();
                if ($source->count() > 0) {
                    $srcset = $source->attr('srcset');
                    if ($srcset) {
                        // srcset can contain multiple URLs, take the first one
                        $urls = explode(',', $srcset);
                        $firstUrl = trim(explode(' ', $urls[0])[0]);
                        if ($firstUrl) {
                            return $this->makeAbsoluteUrl($firstUrl, $baseUrl);
                        }
                    }
                }

                // Fallback to img element inside picture
                $img = $picture->filter('img')->first();
                if ($img->count() > 0) {
                    $src = $img->attr('src') 
                        ?? $img->attr('data-src') 
                        ?? $img->attr('data-lazy-src')
                        ?? $img->attr('data-original');
                    if ($src) {
                        return $this->makeAbsoluteUrl($src, $baseUrl);
                    }
                }
            }

            // Fallback: try to find img directly in category node
            $img = $categoryNode->filter('img')->first();
            if ($img->count() > 0) {
                $src = $img->attr('src') 
                    ?? $img->attr('data-src') 
                    ?? $img->attr('data-lazy-src')
                    ?? $img->attr('data-original');
                if ($src) {
                    return $this->makeAbsoluteUrl($src, $baseUrl);
                }
            }
        } catch (\Exception $e) {
            // Category image not found
        }

        return null;
    }


    /**
     * Make absolute URL from relative URL
     */
    private function makeAbsoluteUrl(string $url, string $baseUrl): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        $parsedBase = parse_url($baseUrl);
        $base = $parsedBase['scheme'] . '://' . $parsedBase['host'];

        if (str_starts_with($url, '/')) {
            return $base . $url;
        }

        return $base . '/' . $url;
    }

    /**
     * Generate slug from URL
     */
    private function generateSlug(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = array_filter(explode('/', $path));
        $lastPart = end($parts);

        return Str::slug($lastPart);
    }
}

