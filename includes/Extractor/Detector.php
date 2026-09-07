<?php
require_once __DIR__ . '/ShopifyExtractor.php';
require_once __DIR__ . '/WooCommerceExtractor.php';

class Detector
{
    public static function detect(string $baseUrl): string
    {
        if (ShopifyExtractor::looksLikeShopify($baseUrl)) {
            return 'shopify';
        }
        if (WooCommerceExtractor::looksLikeWooCommerce($baseUrl)) {
            return 'woocommerce';
        }
        return 'unknown';
    }
}

class Exporter
{
    private const COLUMNS = [
        'title', 'description', 'price', 'compare_at_price', 'currency',
        'sku', 'stock', 'in_stock', 'category', 'tags',
        'image_url', 'variant_count', 'product_url', 'platform',
    ];

    public static function toCsvFile(array $products, string $path): void
    {
        $fh = fopen($path, 'w');
        fputcsv($fh, self::COLUMNS);
        foreach ($products as $p) {
            $row = [];
            foreach (self::COLUMNS as $col) {
                $val = $p[$col] ?? '';
                if (is_bool($val)) {
                    $val = $val ? '1' : '0';
                }
                $row[] = $val;
            }
            fputcsv($fh, $row);
        }
        fclose($fh);
    }

    public static function toJsonFile(array $products, string $path): void
    {
        file_put_contents($path, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
