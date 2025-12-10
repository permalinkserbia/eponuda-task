<?php

namespace App\Services;

use App\Repositories\TelevisionRepositoryInterface;
use App\Repositories\TvCategoryRepositoryInterface;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class CategoryScraperService
{
    public function __construct(
        private readonly TvCategoryRepositoryInterface $categoryRepository,
        private readonly TelevisionRepositoryInterface $televisionRepository,
        private readonly TelevisionScraperService $televisionScraperService
    ) {
    }

    public function scrapeCategory(string $url, ?int $parentId = null): int
    {
        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        // First, find or create the category
        $category = $this->categoryRepository->updateOrCreate(
            ['url' => $url],
            [
                'name' => $this->extractCategoryName($crawler, $url),
                'slug' => $this->generateSlug($url),
                'parent_id' => $parentId,
            ]
        );

        // Find subcategories
        $subcategories = $this->extractSubcategories($crawler, $url);

        $totalProducts = 0;

        // Scrape products from current category
        $totalProducts += $this->scrapeProductsFromPage($crawler, $url, $category->id);

        // Scrape each subcategory
        foreach ($subcategories as $subcategoryUrl) {
            try {
                $subcategory = $this->categoryRepository->updateOrCreate(
                    ['url' => $subcategoryUrl],
                    [
                        'name' => $this->extractCategoryNameFromUrl($subcategoryUrl),
                        'slug' => $this->generateSlug($subcategoryUrl),
                        'parent_id' => $category->id,
                    ]
                );

                $totalProducts += $this->scrapeCategory($subcategoryUrl, $subcategory->id);
            } catch (\Exception $e) {
                \Log::error("Error scraping subcategory {$subcategoryUrl}: " . $e->getMessage());
            }
        }

        return $totalProducts;
    }

    private function fetchHtml(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) {
            throw new \Exception("Failed to fetch URL: {$url} (HTTP {$httpCode})");
        }

        return $html;
    }

    private function extractCategoryName(Crawler $crawler, string $url): string
    {
        try {
            $name = $crawler->filter('h1, .category-title, [class*="category"] h1')->first()->text();
            return trim($name);
        } catch (\Exception $e) {
            return $this->extractCategoryNameFromUrl($url);
        }
    }

    private function extractCategoryNameFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = array_filter(explode('/', $path));
        $lastPart = end($parts);

        return ucwords(str_replace('-', ' ', $lastPart));
    }

    private function extractSubcategories(Crawler $crawler, string $baseUrl): array
    {
        $subcategories = [];

        $selectors = [
            '.subcategory a',
            '.category-item a',
            '[class*="subcategory"] a',
            '.category-list a',
        ];

        foreach ($selectors as $selector) {
            try {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$subcategories, $baseUrl) {
                    $href = $node->attr('href');
                    if ($href) {
                        $fullUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                        if ($fullUrl !== $baseUrl && !in_array($fullUrl, $subcategories)) {
                            $subcategories[] = $fullUrl;
                        }
                    }
                });
            } catch (\Exception $e) {
                continue;
            }
        }

        return $subcategories;
    }

    private function scrapeProductsFromPage(Crawler $crawler, string $url, int $categoryId): int
    {
        $count = 0;

        $crawler->filter('.product-item, .product, [class*="product"]')->each(function (Crawler $node) use (&$count, $url, $categoryId) {
            try {
                $productData = $this->extractProductData($node, $url);
                if ($productData) {
                    $productData['tv_category_id'] = $categoryId;
                    $this->saveProduct($productData);
                    $count++;
                }
            } catch (\Exception $e) {
                \Log::error('Error scraping product: ' . $e->getMessage());
            }
        });

        return $count;
    }

    private function extractProductData(Crawler $node, string $baseUrl): ?array
    {
        $name = $this->extractText($node, [
            '.product-name',
            '.product-title',
            'h2',
            'h3',
            '[class*="name"]',
            '[class*="title"]',
        ]);

        if (!$name) {
            return null;
        }

        $price = $this->extractPrice($node);
        $image = $this->extractImage($node, $baseUrl);
        $productLink = $this->extractLink($node, $baseUrl);
        $externalId = $this->extractExternalId($productLink);
        $specs = $this->extractSpecs($node);

        return [
            'name' => trim($name),
            'price' => $price,
            'image' => $image,
            'product_link' => $productLink,
            'external_id' => $externalId,
            'specs' => $specs,
        ];
    }

    private function extractText(Crawler $node, array $selectors): ?string
    {
        foreach ($selectors as $selector) {
            try {
                $text = $node->filter($selector)->first()->text();
                if (!empty(trim($text))) {
                    return trim($text);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    private function extractPrice(Crawler $node): ?float
    {
        $priceSelectors = [
            '.price',
            '.product-price',
            '[class*="price"]',
            '[data-price]',
        ];

        foreach ($priceSelectors as $selector) {
            try {
                $priceText = $node->filter($selector)->first()->text();
                $price = $this->parsePrice($priceText);
                if ($price !== null) {
                    return $price;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    private function parsePrice(string $priceText): ?float
    {
        $cleaned = preg_replace('/[^\d,.]/', '', $priceText);
        $cleaned = str_replace(',', '.', $cleaned);
        $cleaned = preg_replace('/\.(?=.*\.)/', '', $cleaned);

        return !empty($cleaned) ? (float) $cleaned : null;
    }

    private function extractImage(Crawler $node, string $baseUrl): ?string
    {
        $imageSelectors = [
            'img',
            '.product-image img',
            '[class*="image"] img',
        ];

        foreach ($imageSelectors as $selector) {
            try {
                $img = $node->filter($selector)->first();
                $src = $img->attr('src') ?? $img->attr('data-src') ?? $img->attr('data-lazy-src');
                if ($src) {
                    return $this->makeAbsoluteUrl($src, $baseUrl);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    private function extractLink(Crawler $node, string $baseUrl): ?string
    {
        try {
            $link = $node->filter('a')->first();
            $href = $link->attr('href');
            if ($href) {
                return $this->makeAbsoluteUrl($href, $baseUrl);
            }
        } catch (\Exception $e) {
            try {
                $parent = $node->parents()->filter('a')->first();
                $href = $parent->attr('href');
                if ($href) {
                    return $this->makeAbsoluteUrl($href, $baseUrl);
                }
            } catch (\Exception $e2) {
            }
        }

        return null;
    }

    private function extractExternalId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (preg_match('/\/(\d+)(?:\/|$)/', $url, $matches)) {
            return $matches[1];
        }

        return md5($url);
    }

    private function extractSpecs(Crawler $node): ?string
    {
        $specSelectors = [
            '.specs',
            '.product-specs',
            '[class*="spec"]',
        ];

        foreach ($specSelectors as $selector) {
            try {
                $specs = $node->filter($selector)->first()->text();
                if (!empty(trim($specs))) {
                    return trim($specs);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

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

    private function saveProduct(array $data): void
    {
        if (empty($data['external_id'])) {
            $data['external_id'] = md5($data['name'] . ($data['product_link'] ?? ''));
        }

        $this->televisionRepository->updateOrCreate(
            ['external_id' => $data['external_id']],
            $data
        );
    }

    private function generateSlug(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = array_filter(explode('/', $path));
        $lastPart = end($parts);

        return Str::slug($lastPart);
    }
}

