<?php

declare(strict_types=1);

namespace Blog\Web;

final readonly class BlogApplication
{
    public function __construct(
        private BlogController $controller,
    ) {
    }

    public function handle(string $method, string $path): Response
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $this->controller->notFound();
        }

        $path = '/' . trim(rawurldecode(parse_url($path, PHP_URL_PATH) ?: '/'), '/');

        if ($path === '/') {
            return $this->controller->home(1);
        }

        if (preg_match('#^/pagina/(?<page>[1-9][0-9]*)$#', $path, $matches) === 1) {
            return $this->controller->home((int) $matches['page']);
        }

        if (preg_match('#^/artigos/(?<slug>[a-z0-9]+(?:-[a-z0-9]+)*)$#', $path, $matches) === 1) {
            return $this->controller->article($matches['slug']);
        }

        if ($path === '/sitemap.xml') {
            return $this->controller->sitemap();
        }

        if ($path === '/feed.xml') {
            return $this->controller->feed();
        }

        if ($path === '/robots.txt') {
            return $this->controller->robots();
        }

        if ($path === '/tags') {
            return $this->controller->tags();
        }

        $tagRoute = '#^/tags/(?<tag>[a-z0-9]+(?:-[a-z0-9]+)*)'
            . '(?:/pagina/(?<page>[1-9][0-9]*))?$#';
        if (preg_match($tagRoute, $path, $matches) === 1) {
            $page = isset($matches['page']) ? (int) $matches['page'] : 1;

            return $this->controller->tag($matches['tag'], $page);
        }

        return $this->controller->notFound();
    }
}
