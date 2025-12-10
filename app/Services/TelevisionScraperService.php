<?php

namespace App\Services;

use App\Repositories\TelevisionRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class TelevisionScraperService
{
    public function __construct(
        private readonly TelevisionRepositoryInterface $televisionRepository
    ) {
    }

    public function scrape(string $url): int
    {
        return $this->scrapeForCategory($url);
    }

    /**
     * Scrape products from a URL, optionally associating them with a category
     * This method maintains the same extraction logic as scrape() but allows category association
     */
    public function scrapeForCategory(string $url, ?int $categoryId = null): int
    {
        $html = $this->fetchHtml($url);
        $crawler = new Crawler($html);

        $count = 0;
        // Target products with specific classes: "product b-paging-product b-paging-product--vertical p-paging"
        $crawler->filter('.b-paging-product, .product.b-paging-product, [class*="b-paging-product--vertical"]')->each(function (Crawler $node) use (&$count, $url, $categoryId) {
            try {
                $productData = $this->extractProductData($node, $url);
                if ($productData) {
                    // Add category ID if provided
                    if ($categoryId !== null) {
                        $productData['tv_category_id'] = $categoryId;
                    }
                    $this->saveProduct($productData);
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error('Error scraping product: ' . $e->getMessage());
            }
        });

        return $count;
    }

    protected function fetchHtml(string $url): string
    {
        // Validate URL to prevent SSRF attacks
        if (!\App\Services\UrlValidator::validate($url)) {
            throw new \InvalidArgumentException("Invalid or unsafe URL: {$url}");
        }

        try {
            // Use Browsershot (headless Chrome) to fetch HTML with JavaScript execution
            // This bypasses most bot detection systems
            $browsershot = Browsershot::url($url)
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->waitUntilNetworkIdle()
                ->timeout(60)
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--disable-software-rasterizer',
                    '--disable-extensions',
                ]);

            // Try to set chromium path if available - check multiple locations
            $chromiumPaths = [
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium',
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
                '/snap/bin/chromium',
                getenv('CHROMIUM_PATH'),
                getenv('PUPPETEER_EXECUTABLE_PATH'),
            ];

            foreach ($chromiumPaths as $path) {
                if ($path && file_exists($path) && is_executable($path)) {
                    $browsershot->setChromePath($path);
                    break;
                }
            }

            $html = $browsershot->bodyHtml();

            if (empty($html)) {
                throw new \Exception("Browsershot returned empty HTML for URL: {$url}");
            }

            return $html;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // If it's a Chrome not found error, don't fallback to cURL (it will definitely fail with 403)
            if (str_contains($errorMessage, 'Could not find Chrome') || str_contains($errorMessage, 'ChromeLauncher')) {
                throw new \Exception("Browsershot cannot find Chrome. Please ensure Chrome/Chromium is installed and accessible. Error: {$errorMessage}");
            }
            
            // Fallback to cURL only for other errors
            return $this->fetchHtmlWithCurl($url);
        }
    }

    /**
     * Fallback method using cURL (original implementation)
     */
    private function fetchHtmlWithCurl(string $url): string
    {
        // Create a temporary cookie file for session management
        $cookieFile = sys_get_temp_dir() . '/shoptok_cookies_' . uniqid() . '.txt';

        // Add random delay to avoid rate limiting (1-3 seconds)
        usleep(rand(1000000, 3000000));

        $ch = curl_init();

        // Get a random user agent to avoid detection
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        ];
        $userAgent = $userAgents[array_rand($userAgents)];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_COOKIEFILE => $cookieFile, // Read cookies
            CURLOPT_COOKIEJAR => $cookieFile,  // Write cookies
            CURLOPT_AUTOREFERER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language: sl-SI,sl;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'Cache-Control: max-age=0',
                'DNT: 1',
            ],
            CURLOPT_ENCODING => '', // Enable automatic decompression
            CURLOPT_REFERER => 'https://www.shoptok.si/',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0, // Use HTTP/2 if available
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        // Clean up cookie file
        if (file_exists($cookieFile)) {
            @unlink($cookieFile);
        }

        if ($error) {
            throw new \Exception("cURL error while fetching {$url}: {$error}");
        }


        if ($httpCode === 403) {
            throw new \Exception("Access denied (403) for URL: {$url}. The website is blocking automated requests.");
        }

        if ($httpCode !== 200 || !$html) {
            throw new \Exception("Failed to fetch URL: {$url} (HTTP {$httpCode})");
        }

        return $html;
    }

    private function extractProductData(Crawler $node, string $baseUrl): ?array
    {
        // Try multiple selectors for product name
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

        // Extract price
        $price = $this->extractPrice($node);

        // Ignore products without price
        if ($price === null) {
            return null;
        }

        // Extract image
        $image = $this->extractImage($node, $baseUrl);

        // Extract product link
        $productLink = $this->extractLink($node, $baseUrl);

        // Extract external ID from link
        $externalId = $this->extractExternalId($productLink);

        // Extract specs
        $specs = $this->extractSpecs($node);

        return [
            'name' => $this->sanitizeString(trim($name)),
            'price' => $price,
            'image' => $image ? filter_var($image, FILTER_SANITIZE_URL) : null,
            'product_link' => $productLink ? filter_var($productLink, FILTER_SANITIZE_URL) : null,
            'external_id' => $externalId,
            'specs' => $specs ? $this->sanitizeString($specs) : null,
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
        // Find event-viewitem-price attribute in child nodes of the current product
        // Each product has class "product b-paging-product b-paging-product--vertical p-paging"
        // and contains a child element with event-viewitem-price attribute
        try {
            // First, check if the current node is the product container or find it
            $productNode = $node;
            
            // If current node doesn't have the product classes, check if it's inside one
            $nodeClasses = $node->attr('class') ?? '';
            if (strpos($nodeClasses, 'b-paging-product') === false) {
                // Try to find the product container in ancestors
                $productContainer = $node->ancestors()->filter('.b-paging-product')->first();
                if ($productContainer->count() > 0) {
                    $productNode = $productContainer;
                }
            }
            
            // Primary: Search for any child element with event-viewitem-price attribute within this product
            $priceElement = $productNode->filter('[event-viewitem-price]')->first();
            
            if ($priceElement->count() > 0) {
                $eventPrice = $priceElement->attr('event-viewitem-price');
                if ($eventPrice) {
                    $price = $this->parsePrice($eventPrice);
                    if ($price !== null && $this->isValidPrice($price, $eventPrice)) {
                        return $price;
                    }
                }
            }
            
            // Fallback: Look for element with class b-paging-product__price
            $fallbackPriceNode = $productNode->filter('.b-paging-product__price')->first();
            if ($fallbackPriceNode->count() === 0) {
                // Try with partial class match
                $fallbackPriceNode = $productNode->filter('[class*="b-paging-product__price"]')->first();
            }
            
            if ($fallbackPriceNode->count() > 0) {
                // Try to get price from text content
                $priceText = $fallbackPriceNode->text();
                if (!empty(trim($priceText))) {
                    $price = $this->parsePrice($priceText);
                    if ($price !== null && $this->isValidPrice($price, $priceText)) {
                        return $price;
                    }
                }
                
                // Try data attributes
                $dataPrice = $fallbackPriceNode->attr('data-price') ?? $fallbackPriceNode->attr('data-price-value') ?? $fallbackPriceNode->attr('content');
                if ($dataPrice) {
                    $price = $this->parsePrice($dataPrice);
                    if ($price !== null && $this->isValidPrice($price, $dataPrice)) {
                        return $price;
                    }
                }
            }
        } catch (\Exception $e) {
            // Return null if not found
        }

        return null;
    }

    private function parsePrice(string $priceText): ?float
    {
        if (empty(trim($priceText))) {
            return null;
        }

        // Remove currency symbols and whitespace
        $cleaned = preg_replace('/[^\d,.]/', '', $priceText);
        $cleaned = trim($cleaned);

        if (empty($cleaned)) {
            return null;
        }

        // Handle European format (1.234,56) vs US format (1,234.56)
        // If there are multiple dots or commas, assume European format
        $dotCount = substr_count($cleaned, '.');
        $commaCount = substr_count($cleaned, ',');

        if ($dotCount > 0 && $commaCount > 0) {
            // European format: dots for thousands, comma for decimal
            if ($dotCount > $commaCount) {
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // US format: commas for thousands, dot for decimal
                $cleaned = str_replace(',', '', $cleaned);
            }
        } elseif ($commaCount > 0 && strpos($cleaned, ',') === strlen($cleaned) - 3) {
            // Likely European format with comma as decimal separator
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif ($dotCount > 1) {
            // Multiple dots, likely thousands separators
            $cleaned = str_replace('.', '', $cleaned);
        }

        $price = (float) $cleaned;
        return $price > 0 ? $price : null;
    }

    private function isValidPrice(float $price, string $originalText): bool
    {
        // Price should be in reasonable range for TVs (50 - 5000 EUR typically)
        // Most TVs are under 5000 EUR, very few exceed this
        if ($price < 50 || $price > 5000) {
            return false;
        }

        // Reject prices that look like years (2020-2030 range)
        if ($price >= 2020 && $price <= 2030) {
            return false;
        }

        // Reject prices that look like model numbers (e.g., 4302 for a 32" TV model)
        // If price is very high and the original text doesn't have currency indicators, it's likely wrong
        $hasCurrency = preg_match('/[€EUR]|euro|cen/i', $originalText);

        if (!$hasCurrency && $price > 2000) {
            // Be more strict for high prices without currency indicators
            return false;
        }

        return true;
    }

    private function extractImage(Crawler $node, string $baseUrl): ?string
    {
        // First, try to find img with class "lazy" (primary selector for product thumbnails)
        $imageSelectors = [
            'img.lazy',
            '[class*="lazy"] img',
            'img[class*="lazy"]',
            'img',
            '.product-image img',
            '[class*="image"] img',
        ];

        foreach ($imageSelectors as $selector) {
            try {
                $img = $node->filter($selector)->first();
                if ($img->count() > 0) {
                    // For lazy-loaded images, check data-src, data-lazy-src, or src
                    $src = $img->attr('data-src') 
                        ?? $img->attr('data-lazy-src') 
                        ?? $img->attr('data-original')
                        ?? $img->attr('src');
                    
                    if ($src) {
                        return $this->makeAbsoluteUrl($src, $baseUrl);
                    }
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
            // Try to find link in parent
            try {
                $parent = $node->ancestors()->filter('a')->first();
                if ($parent->count() > 0) {
                    $href = $parent->attr('href');
                    if ($href) {
                        return $this->makeAbsoluteUrl($href, $baseUrl);
                    }
                }
            } catch (\Exception $e2) {
                // No link found
            }
        }

        return null;
    }

    private function extractExternalId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // Extract ID from URL or generate hash
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

    /**
     * Sanitize string input to prevent XSS
     */
    private function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
    }
}

