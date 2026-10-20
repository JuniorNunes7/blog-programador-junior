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
        return $this->render('layout', [
            'content' => $this->render($template, $data),
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
