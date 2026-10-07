<?php

namespace App\Services;

class PdfTextExtractor
{
    public function extract(string $path): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('shell_exec', $disabled, true)) {
            return null;
        }

        $binary = trim((string) shell_exec('command -v pdftotext'));
        if ($binary === '') {
            return null;
        }

        $text = shell_exec('pdftotext -q -enc UTF-8 '.escapeshellarg($path).' - 2>/dev/null');
        if (! is_string($text)) {
            return null;
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);

        return $text === '' ? null : mb_substr($text, 0, 20000);
    }
}
