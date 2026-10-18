<?php

declare(strict_types=1);

namespace Blog\Web;

final class TagSlugger
{
    public static function slug(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $transliterated === false ? $value : $transliterated;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
