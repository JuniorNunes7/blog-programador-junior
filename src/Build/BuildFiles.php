<?php

declare(strict_types=1);

namespace Blog\Build;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class BuildFiles
{
    public static function directory(string $path): void
    {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('Não foi possível criar o diretório: ' . $path);
        }
    }

    /** @return array<string, string> Relative paths and SHA-256 hashes, in stable order. */
    public static function inventory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new RuntimeException('Diretório não encontrado: ' . $directory);
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isLink() || !$file->isFile()) {
                throw new RuntimeException('O build aceita somente arquivos regulares: ' . $file->getPathname());
            }
            $relative = substr($file->getPathname(), strlen($directory) + 1);
            $files[$relative] = self::hash($file->getPathname());
        }
        ksort($files);

        return $files;
    }

    public static function hash(string $path): string
    {
        $hash = @hash_file('sha256', $path);
        if ($hash === false) {
            throw new RuntimeException('Não foi possível ler: ' . $path);
        }

        return $hash;
    }

    public static function write(string $path, string $contents): void
    {
        self::directory(dirname($path));
        if (@file_put_contents($path, $contents) !== strlen($contents)) {
            throw new RuntimeException('Não foi possível gravar: ' . $path);
        }
    }

    public static function copy(string $source, string $destination): void
    {
        self::directory(dirname($destination));
        if (!@copy($source, $destination) || !@touch($destination, filemtime($source))) {
            throw new RuntimeException('Não foi possível copiar: ' . $source);
        }
    }

    public static function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            if (!@unlink($path)) {
                throw new RuntimeException('Não foi possível remover: ' . $path);
            }
        } elseif (is_dir($path)) {
            foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $file) {
                self::remove($file->getPathname());
            }
            if (!@rmdir($path)) {
                throw new RuntimeException('Não foi possível remover: ' . $path);
            }
        }
    }
}
