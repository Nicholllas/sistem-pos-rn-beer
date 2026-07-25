<?php

namespace App\Support;

use Illuminate\Support\Str;

final class TextNormalizer
{
    public static function singleLine(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        // Buang null byte, control character, dan zero-width character.
        $value = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u',
            '',
            $value
        ) ?? $value;

        // Semua whitespace, tab, dan baris baru menjadi satu spasi.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return Str::upper($value);
    }

    public static function multiline(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        // Buang karakter kontrol berbahaya, tetapi pertahankan tab/newline.
        $value = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u',
            '',
            $value
        ) ?? $value;

        // Samakan semua tipe line break.
        $value = preg_replace('/\R/u', "\n", $value) ?? $value;

        // Rapikan spasi horizontal tanpa menghapus baris baru.
        $value = preg_replace('/[^\S\n]+/u', ' ', $value) ?? $value;

        // Maksimal dua baris baru berturut-turut.
        $value = preg_replace("/\n{3,}/u", "\n\n", $value) ?? $value;

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return Str::upper($value);
    }
}
