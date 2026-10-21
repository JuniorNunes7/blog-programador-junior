<?php

declare(strict_types=1);

namespace Blog\Web;

/** Local preview only; reads generated files and never renders content. */
final readonly class StaticFileServer
{
    public function __construct(private string $directory)
    {
    }

    public function serve(string $method, string $uri): void
    {
        if (!is_file($this->directory . '/index.html')) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Gere o site com composer build antes de iniciar a prévia.';
            return;
        }
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        if (
            !in_array($method, ['GET', 'HEAD'], true) || str_contains($path, "\0")
            || str_contains($path, '\\') || preg_match('#(^|/)\.#', $path) === 1
        ) {
            $this->send('/404.html', 404, $method);
            return;
        }
        $canonical = '/' . trim($path, '/');
        if (in_array($canonical, ['/404', '/404.html'], true)) {
            $this->send('/404.html', 404, $method);
            return;
        }
        if ($canonical === '/index.html' || $canonical === '/pagina/1') {
            $canonical = '/';
        } elseif (preg_match('#^(/tags/[a-z0-9-]+)/pagina/1$#', $canonical, $matches) === 1) {
            $canonical = $matches[1];
        } elseif (str_ends_with($canonical, '.html') && $canonical !== '/404.html') {
            $canonical = substr($canonical, 0, -5);
        }
        $file = $canonical === '/' ? '/index.html' : $canonical . '.html';
        if ($this->exists($file)) {
            if ($canonical !== $path) {
                $query = parse_url($uri, PHP_URL_QUERY);
                header('Location: ' . $canonical . ($query !== null ? '?' . $query : ''), true, 301);
                return;
            }
            $this->send($file, 200, $method);
        } elseif ($this->exists($path)) {
            $this->send($path, 200, $method);
        } else {
            $this->send('/404.html', 404, $method);
        }
    }

    private function exists(string $path): bool
    {
        $root = realpath($this->directory);
        $file = realpath($this->directory . $path);

        return $root !== false && $file !== false && str_starts_with($file, $root . '/') && is_file($file);
    }

    private function send(string $path, int $status, string $method): void
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $type = match ($extension) {
            'html' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'xml' => $path === '/feed.xml' ? 'application/rss+xml; charset=UTF-8' : 'application/xml; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'woff', 'woff2' => 'font/' . $extension,
            default => 'application/octet-stream',
        };
        http_response_code($status);
        header('Content-Type: ' . $type);
        if ($method !== 'HEAD') {
            readfile($this->directory . $path);
        }
    }
}
