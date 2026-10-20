<?php

declare(strict_types=1);

namespace Blog\Web;

use Blog\Content\Model\Article;
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
        $canonicalUrl = $this->absoluteUrl($path);
        $schema = $article === null
            ? $this->websiteSchema()
            : $this->blogPostingSchema($article, $canonicalUrl);

        return $this->render('layout', [
            'article' => $article,
            'canonicalUrl' => $canonicalUrl,
            'content' => $this->render($template, $data + ['site' => $this->site]),
            'description' => $description,
            'schemaJson' => json_encode(
                $schema,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
            ),
            'site' => $this->site,
            'title' => $title,
            'noindex' => $data['noindex'] ?? false,
            'pageStyle' => basename($template),
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

    public function absoluteUrl(string $path): string
    {
        return rtrim($this->site['base_url'], '/') . '/' . ltrim($path, '/');
    }

    public function asset(string $path): string
    {
        $file = dirname($this->templateDirectory) . '/public' . $path;
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('O asset "%s" não existe.', $path));
        }

        return $path . '?v=' . substr(hash_file('sha256', $file), 0, 12);
    }

    public function date(string $date): string
    {
        $months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $publishedAt = new \DateTimeImmutable($date);

        return $publishedAt->format('d') . ' ' . $months[(int) $publishedAt->format('n') - 1]
            . ' ' . $publishedAt->format('Y');
    }

    public function articleUrl(string $slug): string
    {
        return '/artigos/' . rawurlencode($slug);
    }

    public function tagUrl(string $tag): string
    {
        return '/tags/' . rawurlencode(TagSlugger::slug($tag));
    }

    /** @return array<string, string> */
    private function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->site['name'],
            'url' => rtrim($this->site['base_url'], '/') . '/',
        ];
    }

    /** @return array<string, string|array<string, string>> */
    private function blogPostingSchema(Article $article, string $canonicalUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $article->metadata->title,
            'description' => $article->metadata->description,
            'datePublished' => $article->metadata->publishedAt->format('Y-m-d'),
            'dateModified' => $article->metadata->publishedAt->format('Y-m-d'),
            'mainEntityOfPage' => $canonicalUrl,
            'image' => $this->absoluteUrl('/assets/brand/banner-light.png'),
            'author' => ['@type' => 'Person', 'name' => $this->site['author']],
            'publisher' => ['@type' => 'Person', 'name' => $this->site['author']],
        ];
    }
}
