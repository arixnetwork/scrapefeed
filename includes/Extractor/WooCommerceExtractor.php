<?php
require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/text_helpers.php';

class WooCommerceExtractor
{
    private const PAGE_SIZE = 100;
    private const MAX_PAGES = 200;

    public static function looksLikeWooCommerce(string $baseUrl): bool
    {
        if (self::tryStoreApi($baseUrl) || self::tryWpV2($baseUrl)) {
            return true;
        }
        try {
            [$status, $body] = Http::get("$baseUrl/shop/");
            return $status === 200 && stripos($body, 'woocommerce') !== false;
        } catch (Throwable) {
            return false;
        }
    }

    public static function extract(string $baseUrl, ?int $limit = null): array
    {
        if (self::tryStoreApi($baseUrl)) {
            return self::extractStoreApi($baseUrl, $limit);
        }
        if (self::tryWpV2($baseUrl)) {
            return self::extractWpV2($baseUrl, $limit);
        }
        return self::extractHtml($baseUrl, $limit);
    }

    // ---------- Tier 1: WooCommerce Store API ----------

    private static function tryStoreApi(string $baseUrl): bool
    {
        try {
            [$status, $body] = Http::get("$baseUrl/wp-json/wc/store/v1/products", ['per_page' => 1]);
            return $status === 200 && is_array(json_decode($body, true));
        } catch (Throwable) {
            return false;
        }
    }

    private static function extractStoreApi(string $baseUrl, ?int $limit): array
    {
        $products = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            [$status, $body] = Http::get("$baseUrl/wp-json/wc/store/v1/products", [
                'per_page' => self::PAGE_SIZE,
                'page' => $page,
            ]);
            if ($status !== 200) {
                break;
            }
            $items = json_decode($body, true);
            if (!$items) {
                break;
            }
            foreach ($items as $item) {
                $products[] = self::normalizeStoreApi($item);
                if ($limit && count($products) >= $limit) {
                    return $products;
                }
            }
            if (count($items) < self::PAGE_SIZE) {
                break;
            }
            usleep(300_000);
        }
        return $products;
    }

    private static function normalizeStoreApi(array $raw): array
    {
        $prices = $raw['prices'] ?? [];
        $minorUnit = (int) ($prices['currency_minor_unit'] ?? 2);
        $divisor = 10 ** $minorUnit;

        $money = function (string $key) use ($prices, $divisor): ?float {
            $val = $prices[$key] ?? null;
            if ($val === null || $val === '') {
                return null;
            }
            return ((int) $val) / $divisor;
        };

        $images = array_values(array_filter(array_map(
            fn($img) => $img['src'] ?? '',
            $raw['images'] ?? []
        )));
        $categories = array_values(array_filter(array_map(
            fn($c) => $c['name'] ?? '',
            $raw['categories'] ?? []
        )));

        return [
            'platform' => 'woocommerce',
            'title' => $raw['name'] ?? '',
            'description' => clean_html_to_text($raw['short_description'] ?? $raw['description'] ?? ''),
            'price' => $money('price'),
            'compare_at_price' => $money('regular_price'),
            'currency' => $prices['currency_code'] ?? '',
            'sku' => $raw['sku'] ?? '',
            'stock' => $raw['stock_quantity'] ?? null,
            'in_stock' => $raw['is_in_stock'] ?? null,
            'category' => implode(', ', $categories),
            'tags' => '',
            'image_url' => $images[0] ?? '',
            'variant_count' => count($raw['variations'] ?? []),
            'product_url' => $raw['permalink'] ?? '',
        ];
    }

    // ---------- Tier 2: WP core REST API for `product` post type ----------

    private static function tryWpV2(string $baseUrl): bool
    {
        try {
            [$status, $body] = Http::get("$baseUrl/wp-json/wp/v2/product", ['per_page' => 1]);
            return $status === 200 && is_array(json_decode($body, true));
        } catch (Throwable) {
            return false;
        }
    }

    private static function extractWpV2(string $baseUrl, ?int $limit): array
    {
        $products = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            [$status, $body] = Http::get("$baseUrl/wp-json/wp/v2/product", [
                'per_page' => self::PAGE_SIZE,
                'page' => $page,
            ]);
            if ($status !== 200) {
                break;
            }
            $items = json_decode($body, true);
            if (!$items) {
                break;
            }
            foreach ($items as $item) {
                $products[] = [
                    'platform' => 'woocommerce',
                    'title' => clean_html_to_text($item['title']['rendered'] ?? ''),
                    'description' => clean_html_to_text($item['excerpt']['rendered'] ?? ''),
                    'price' => null,
                    'compare_at_price' => null,
                    'currency' => '',
                    'sku' => '',
                    'stock' => null,
                    'in_stock' => null,
                    'category' => '',
                    'tags' => '',
                    'image_url' => '',
                    'variant_count' => 0,
                    'product_url' => $item['link'] ?? '',
                ];
                if ($limit && count($products) >= $limit) {
                    return $products;
                }
            }
            if (count($items) < self::PAGE_SIZE) {
                break;
            }
            usleep(300_000);
        }
        return $products;
    }

    // ---------- Tier 3: HTML fallback ----------

    private static function extractHtml(string $baseUrl, ?int $limit): array
    {
        $products = [];
        $maxPages = 40;

        for ($page = 1; $page <= $maxPages; $page++) {
            $url = $page > 1 ? "$baseUrl/shop/page/$page/" : "$baseUrl/shop/";
            try {
                [$status, $body] = Http::get($url);
            } catch (Throwable) {
                break;
            }
            if ($status !== 200) {
                break;
            }

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($body);
            libxml_clear_errors();
            $xpath = new DOMXPath($dom);

            $cards = $xpath->query("//li[contains(concat(' ', normalize-space(@class), ' '), ' product ')]");
            if ($cards->length === 0) {
                $cards = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' type-product ')]");
            }
            if ($cards->length === 0) {
                break;
            }

            foreach ($cards as $card) {
                $titleNode = $xpath->query(".//h2 | .//h3", $card)->item(0);
                $priceNode = $xpath->query(".//*[contains(@class,'price')]", $card)->item(0);
                $linkNode = $xpath->query(".//a", $card)->item(0);
                $imgNode = $xpath->query(".//img", $card)->item(0);

                $title = $titleNode ? trim($titleNode->textContent) : '';
                if ($title === '') {
                    continue;
                }

                $products[] = [
                    'platform' => 'woocommerce',
                    'title' => $title,
                    'description' => '',
                    'price' => $priceNode ? self::parsePrice($priceNode->textContent) : null,
                    'compare_at_price' => null,
                    'currency' => '',
                    'sku' => '',
                    'stock' => null,
                    'in_stock' => null,
                    'category' => '',
                    'tags' => '',
                    'image_url' => $imgNode ? ($imgNode->getAttribute('data-src') ?: $imgNode->getAttribute('src')) : '',
                    'variant_count' => 0,
                    'product_url' => $linkNode ? $linkNode->getAttribute('href') : '',
                ];

                if ($limit && count($products) >= $limit) {
                    return $products;
                }
            }
            usleep(300_000);
        }

        return $products;
    }

    private static function parsePrice(string $text): ?float
    {
        if (preg_match('/[\d.,]+/', $text, $m)) {
            $cleaned = str_replace(',', '', $m[0]);
            return is_numeric($cleaned) ? (float) $cleaned : null;
        }
        return null;
    }
}
