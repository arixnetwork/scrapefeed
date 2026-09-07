<?php
require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/text_helpers.php';

class ShopifyExtractor
{
    private const PAGE_SIZE = 250;
    private const MAX_PAGES = 200;

    public static function looksLikeShopify(string $baseUrl): bool
    {
        try {
            [$status, $body] = Http::get("$baseUrl/products.json", ['limit' => 1]);
            if ($status !== 200) {
                return false;
            }
            $data = json_decode($body, true);
            return is_array($data) && array_key_exists('products', $data);
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<int, array> normalized product rows */
    public static function extract(string $baseUrl, ?int $limit = null): array
    {
        $products = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            [$status, $body] = Http::get("$baseUrl/products.json", [
                'limit' => self::PAGE_SIZE,
                'page' => $page,
            ]);
            if ($status !== 200) {
                break;
            }
            $data = json_decode($body, true);
            $raw = $data['products'] ?? [];
            if (!$raw) {
                break;
            }

            foreach ($raw as $item) {
                $products[] = self::normalize($item, $baseUrl);
                if ($limit && count($products) >= $limit) {
                    return $products;
                }
            }

            if (count($raw) < self::PAGE_SIZE) {
                break;
            }
            usleep(300_000); // be polite between pages
        }

        return $products;
    }

    private static function normalize(array $raw, string $baseUrl): array
    {
        $images = array_values(array_filter(array_map(
            fn($img) => $img['src'] ?? '',
            $raw['images'] ?? []
        )));

        $variants = $raw['variants'] ?? [];
        $first = $variants[0] ?? null;
        $handle = $raw['handle'] ?? '';

        $tags = $raw['tags'] ?? '';
        if (is_array($tags)) {
            $tags = implode(', ', $tags);
        }

        return [
            'platform' => 'shopify',
            'title' => $raw['title'] ?? '',
            'description' => clean_html_to_text($raw['body_html'] ?? ''),
            'price' => $first ? safe_float($first['price'] ?? null) : null,
            'compare_at_price' => $first ? safe_float($first['compare_at_price'] ?? null) : null,
            'currency' => '',
            'sku' => $first['sku'] ?? '',
            'stock' => null,
            'in_stock' => $first['available'] ?? null,
            'category' => $raw['product_type'] ?? '',
            'tags' => $tags,
            'image_url' => $images[0] ?? '',
            'variant_count' => count($variants),
            'product_url' => $handle ? "$baseUrl/products/$handle" : '',
        ];
    }
}
