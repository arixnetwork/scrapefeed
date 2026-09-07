<?php

function clean_html_to_text(string $html): string
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    $text = preg_replace('/\s+([.,!?;:])/', '$1', $text);
    return $text;
}

function safe_float($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    return is_numeric($value) ? (float) $value : null;
}
