<?php
require_once __DIR__ . '/Extractor/Detector.php';

class JobRunner
{
    public static function run(int $extractionId): void
    {
        $db = Database::get();
        $stmt = $db->prepare('SELECT * FROM extractions WHERE id = ?');
        $stmt->execute([$extractionId]);
        $job = $stmt->fetch();
        if (!$job || $job['status'] !== 'pending') {
            return;
        }

        $db->prepare('UPDATE extractions SET status = "running" WHERE id = ?')->execute([$extractionId]);

        try {
            $baseUrl = Http::normalizeBaseUrl($job['store_url']);

            $platform = $job['platform'];
            if ($platform === 'auto') {
                $platform = Detector::detect($baseUrl);
            }
            if ($platform === 'unknown') {
                throw new RuntimeException('Could not detect Shopify or WooCommerce on this URL.');
            }

            $limit = $job['product_limit'] ? (int) $job['product_limit'] : null;
            $products = $platform === 'shopify'
                ? ShopifyExtractor::extract($baseUrl, $limit)
                : WooCommerceExtractor::extract($baseUrl, $limit);

            if (!$products) {
                throw new RuntimeException('No products found at this URL.');
            }

            if (!is_dir(EXPORTS_DIR)) {
                mkdir(EXPORTS_DIR, 0750, true);
            }
            $filename = 'export_' . $extractionId . '_' . bin2hex(random_bytes(4)) . '.' . $job['format'];
            $path = EXPORTS_DIR . '/' . $filename;

            if ($job['format'] === 'json') {
                Exporter::toJsonFile($products, $path);
            } else {
                Exporter::toCsvFile($products, $path);
            }

            $db->prepare(
                'UPDATE extractions
                 SET status = "done", detected_platform = ?, product_count = ?, result_file = ?, completed_at = NOW()
                 WHERE id = ?'
            )->execute([$platform, count($products), $filename, $extractionId]);

            // deduct one credit per product, floor 1 credit for the run
            $cost = max(1, count($products));
            $db->prepare('UPDATE users SET credits_remaining = GREATEST(0, credits_remaining - ?) WHERE id = ?')
               ->execute([$cost, $job['user_id']]);

        } catch (Throwable $e) {
            $db->prepare(
                'UPDATE extractions SET status = "failed", error_message = ?, completed_at = NOW() WHERE id = ?'
            )->execute([substr($e->getMessage(), 0, 500), $extractionId]);
        }
    }
}
