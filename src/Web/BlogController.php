<?php

declare(strict_types=1);

namespace Blog\Web;

use Blog\Content\Contract\ArticleRepository;
use League\CommonMark\CommonMarkConverter;

final readonly class BlogController
{
    /** @param array{name: string, description: string, author: string, base_url: string, articles_per_page: int} $site */
    public function __construct(
        private array $site,
        private ArticleIndex $index,
        private ArticleRepository $articles,
        private CommonMarkConverter $markdown,
        private TemplateRenderer $templates,
    ) {
    }

    public function home(int $page): Response
    {
        return $this->listing($this->index->articles(), 'Artigos recentes', $page, '/');
    }

    public function tag(string $tagSlug, int $page): Response
    {
        $tag = $this->index->tags()[$tagSlug] ?? null;
        if ($tag === null) {
            return $this->notFound();
        }

        return $this->listing(
            $this->index->forTag($tagSlug),
            sprintf('Artigos sobre %s', $tag['name']),
            $page,
            '/tags/' . $tagSlug,
        );
    }

    public function article(string $slug): Response
    {
        if ($this->index->find($slug) === null) {
            return $this->notFound();
        }

        $article = $this->articles->find($slug);
        if ($article === null) {
            return $this->notFound();
        }

        return new Response(200, $this->templates->page($article->metadata->title, 'pages/article', [
            'article' => $article,
            'articleHtml' => $this->markdown->convert($article->markdown)->getContent(),
        ]));
    }

    public function notFound(): Response
    {
        return new Response(404, $this->templates->page('Página não encontrada', 'pages/not-found'));
    }

    /** @param list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}> $articles */
    private function listing(array $articles, string $heading, int $page, string $basePath): Response
    {
        $totalPages = max(1, (int) ceil(count($articles) / $this->site['articles_per_page']));
        if ($page > $totalPages) {
            return $this->notFound();
        }

        $visibleArticles = array_slice(
            $articles,
            ($page - 1) * $this->site['articles_per_page'],
            $this->site['articles_per_page'],
        );

        return new Response(200, $this->templates->page($heading, 'pages/listing', [
            'articles' => $visibleArticles,
            'description' => $this->site['description'],
            'heading' => $heading,
            'nextUrl' => $page < $totalPages ? $this->pageUrl($basePath, $page + 1) : null,
            'page' => $page,
            'previousUrl' => $page > 1 ? $this->pageUrl($basePath, $page - 1) : null,
            'totalPages' => $totalPages,
        ]));
    }

    private function pageUrl(string $basePath, int $page): string
    {
        if ($page === 1) {
            return $basePath;
        }

        return $basePath === '/' ? '/pagina/' . $page : $basePath . '/pagina/' . $page;
    }
}
