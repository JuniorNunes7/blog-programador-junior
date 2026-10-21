<?php

declare(strict_types=1);

namespace Blog\Web;

final class Environment
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = substr($line, strlen('export '));
            }

            if (preg_match('/^(?<key>[A-Za-z_][A-Za-z0-9_]*)\s*=\s*(?<value>.*)$/', $line, $matches) !== 1) {
                continue;
            }

            $key = $matches['key'];
            if (getenv($key) !== false || array_key_exists($key, $_ENV)) {
                continue;
            }

            $value = self::parseValue($matches['value']);
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }

    private static function parseValue(string $value): string
    {
        $value = trim($value);
        if (strlen($value) < 2 || $value[0] !== $value[strlen($value) - 1]) {
            return preg_replace('/\s+#.*$/', '', $value) ?? $value;
        }

        $quote = $value[0];
        if ($quote !== '"' && $quote !== "'") {
            return $value;
        }

        $value = substr($value, 1, -1);

        return $quote === '"'
            ? strtr($value, ['\\n' => "\n", '\\r' => "\r", '\\"' => '"', '\\\\' => '\\'])
            : $value;
    }
}
