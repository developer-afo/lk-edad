<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    public function extract(string $path): ?string
    {
        $text = $this->viaPoppler($path) ?: $this->viaParser($path);

        return $this->clean($text);
    }

    private function viaPoppler(string $path): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('shell_exec', $disabled, true)) {
            return null;
        }

        $binary = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));
        if ($binary === '') {
            return null;
        }

        $text = shell_exec($binary.' -q -enc UTF-8 '.escapeshellarg($path).' - 2>/dev/null');

        return is_string($text) ? $text : null;
    }

    private function viaParser(string $path): ?string
    {
        try {
            $pdf = (new Parser())->parseFile($path);

            return $pdf->getText();
        } catch (\Throwable) {
            return null;
        }
    }

    private function clean(?string $text): ?string
    {
        if (! is_string($text)) {
            return null;
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);

        return $text === '' ? null : mb_substr($text, 0, 20000);
    }
}
