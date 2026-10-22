<?php

declare(strict_types=1);

namespace Blog\Build;

use JsonException;

final class BuildManifest
{
    public const int VERSION = 1;

    /** @return array<string, mixed>|null */
    public static function read(string $path): ?array
    {
        $json = @file_get_contents($path);
        if ($json === false) {
            return null;
        }
        try {
            $manifest = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (
            !is_array($manifest) || ($manifest['version'] ?? null) !== self::VERSION
            || !is_string($manifest['global'] ?? null)
            || !is_array($manifest['articles'] ?? null) || !is_array($manifest['outputs'] ?? null)
            || $manifest['outputs'] === []
        ) {
            return null;
        }
        foreach ($manifest['articles'] as $slug => $article) {
            $slug = (string) $slug;
            if (
                !is_string($slug) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1
                || !is_array($article) || !self::validHash($article['hash'] ?? null)
                || !self::validMetadata($article['metadata'] ?? null, $slug)
            ) {
                return null;
            }
        }
        foreach ($manifest['outputs'] as $path => $hash) {
            if (!is_string($path) || !self::validHash($hash)) {
                return null;
            }
        }

        return $manifest;
    }

    private static function validHash(mixed $hash): bool
    {
        return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) === 1;
    }

    private static function validMetadata(mixed $metadata, string $slug): bool
    {
        if (!is_array($metadata) || ($metadata['slug'] ?? null) !== $slug) {
            return false;
        }
        foreach (['title', 'date', 'description'] as $field) {
            if (!is_string($metadata[$field] ?? null)) {
                return false;
            }
        }
        foreach (['keywords', 'tags'] as $field) {
            if (!is_array($metadata[$field] ?? null) || !array_is_list($metadata[$field])) {
                return false;
            }
            foreach ($metadata[$field] as $value) {
                if (!is_string($value)) {
                    return false;
                }
            }
        }

        return true;
    }
}
