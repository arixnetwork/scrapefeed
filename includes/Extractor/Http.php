<?php
/**
 * Minimal cURL GET helper shared by every extractor. Returns
 * [statusCode, bodyString] so callers decide how to parse it.
 */
class Http
{
    private const USER_AGENT = 'scrapefeed/1.0 (+product data export tool)';
    private const TIMEOUT = 15;

    public static function get(string $url, array $query = []): array
    {
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => ['Accept: application/json, text/html'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException("Request to $url failed: $error");
        }

        return [$status, $body];
    }

    /** Normalize whatever the user typed into a clean https://host base URL. */
    public static function normalizeBaseUrl(string $input): string
    {
        $input = trim($input);
        if (!preg_match('#^https?://#i', $input)) {
            $input = 'https://' . $input;
        }
        $parts = parse_url($input);
        if (!$parts || empty($parts['host'])) {
            throw new InvalidArgumentException('That does not look like a valid URL.');
        }
        $scheme = $parts['scheme'] ?? 'https';
        return "$scheme://{$parts['host']}";
    }
}
