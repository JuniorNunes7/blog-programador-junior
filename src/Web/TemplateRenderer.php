<?php

declare(strict_types=1);

namespace Blog\Web;

use RuntimeException;

final readonly class TemplateRenderer
{
    /** @param array{name: string, description: string, author: string, base_url: string, articles_per_page: int} $site */
    public function __construct(
        private string $templateDirectory,
        private array $site,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function page(string $title, string $template, array $data = []): string
    {
        $article = $data['article'] ?? null;
        $path = $data['path'] ?? '/';
        $description = $data['description'] ?? $this->site['description'];
        $canonicalUrl = rtrim($this->site['base_url'], '/') . '/' . ltrim($path, '/');
        $schema = $article === null
            ? ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $this->site['name'], 'url' => rtrim($this->site['base_url'], '/') . '/']
            : ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $article->metadata->title, 'description' => $article->metadata->description, 'datePublished' => $article->metadata->publishedAt->format('Y-m-d'), 'dateModified' => $article->metadata->publishedAt->format('Y-m-d'), 'mainEntityOfPage' => $canonicalUrl, 'author' => ['@type' => 'Person', 'name' => $this->site['author']], 'publisher' => ['@type' => 'Person', 'name' => $this->site['author']]];

        return $this->render('layout', [
            'article' => $article,
            'canonicalUrl' => $canonicalUrl,
            'content' => $this->render($template, $data),
            'description' => $description,
            'schemaJson' => json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'site' => $this->site,
            'title' => $title,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        $path = $this->templateDirectory . '/' . $template . '.php';
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('O template "%s" não existe.', $template));
        }

        ob_start();
        extract($data, EXTR_SKIP);
        require $path;

        return (string) ob_get_clean();
    }

    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function articleUrl(string $slug): string
    {
        return '/artigos/' . rawurlencode($slug);
    }

    public function tagUrl(string $tag): string
    {
        return '/tags/' . rawurlencode(TagSlugger::slug($tag));
    }
}
