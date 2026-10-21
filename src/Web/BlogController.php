<?php

declare(strict_types=1);

namespace Blog\Web;

use Blog\Content\Contract\ArticleRepository;

final readonly class BlogController
{
    /** @param array{name: string, description: string, author: string, base_url: string, articles_per_page: int, social: array{linkedin: ?string, github: ?string, instagram: ?string}} $site */
    public function __construct(
        private array $site,
        private ArticleIndex $index,
        private ArticleRepository $articles,
        private ArticleRenderer $articleRenderer,
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

    public function tags(): Response
    {
        $tags = $this->index->tags();

        return new Response(200, $this->templates->page('Todos os assuntos', 'pages/tags', [
            'description' => 'Todos os assuntos publicados por ' . $this->site['author']
                . ' no ' . $this->site['name'] . '.',
            'path' => '/tags',
            'tags' => $tags,
        ]));
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

        $body = $this->templates->page($article->metadata->title, 'pages/article', [
            'article' => $article,
            'articleHtml' => $this->articleRenderer->render($article),
            'description' => $article->metadata->description,
            'path' => '/artigos/' . $slug,
        ]);
        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
            'ETag' => '"' . hash('sha256', $body) . '"',
        ];
        $modifiedAt = $this->articles->modifiedAt($slug);
        if ($modifiedAt !== null) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $modifiedAt) . ' GMT';
        }

        return new Response(200, $body, $headers);
    }

    public function notFound(): Response
    {
        return new Response(404, $this->templates->page('Página não encontrada', 'pages/not-found', [
            'noindex' => true,
        ]));
    }

    public function sitemap(): Response
    {
        $urls = [
            ['url' => $this->absoluteUrl('/')],
            ['url' => $this->absoluteUrl('/tags')],
        ];
        foreach ($this->index->articles() as $article) {
            $urls[] = [
                'url' => $this->absoluteUrl('/artigos/' . $article['slug']),
                'lastModified' => $article['date'],
            ];
        }
        foreach (array_keys($this->index->tags()) as $tag) {
            $urls[] = ['url' => $this->absoluteUrl('/tags/' . $tag)];
        }

        return new Response(200, $this->templates->render('seo/sitemap', ['urls' => $urls]), [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function feed(): Response
    {
        $items = array_map(function (array $article): array {
            $date = new \DateTimeImmutable($article['date'] . 'T00:00:00+00:00');

            return [
                'title' => $article['title'],
                'url' => $this->absoluteUrl('/artigos/' . $article['slug']),
                'description' => $article['description'],
                'publishedAt' => $date->format(DATE_RSS),
            ];
        }, $this->index->articles());

        return new Response(200, $this->templates->render('seo/feed', [
            'items' => $items,
            'site' => $this->site,
            'siteUrl' => $this->absoluteUrl('/'),
        ]), [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots(): Response
    {
        return new Response(200, $this->templates->render('seo/robots', [
            'sitemapUrl' => $this->absoluteUrl('/sitemap.xml'),
        ]), [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
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

        $title = $heading . ($page > 1 ? ' — Página ' . $page : '');
        $description = $basePath === '/'
            ? $this->site['description']
            : $heading . '. Textos de ' . $this->site['author'] . ' no ' . $this->site['name'] . '.';
        if ($page > 1) {
            $description .= ' Página ' . $page . '.';
        }

        $activeTag = $basePath === '/' ? null : substr($basePath, strlen('/tags/'));
        $availableTags = $this->index->tags();
        $featuredTags = $this->index->featuredTags();
        if ($activeTag !== null && isset($availableTags[$activeTag]) && !isset($featuredTags[$activeTag])) {
            $featuredTags[$activeTag] = $availableTags[$activeTag];
        }

        return new Response(200, $this->templates->page($title, 'pages/listing', [
            'articles' => $visibleArticles,
            'description' => $description,
            'heading' => $heading,
            'path' => $this->pageUrl($basePath, $page),
            'isHome' => $basePath === '/' && $page === 1,
            'activeTag' => $activeTag,
            'featuredTags' => $featuredTags,
            'nextUrl' => $page < $totalPages ? $this->pageUrl($basePath, $page + 1) : null,
            'page' => $page,
            'previousUrl' => $page > 1 ? $this->pageUrl($basePath, $page - 1) : null,
            'tagCount' => count($availableTags),
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

    private function absoluteUrl(string $path): string
    {
        return rtrim($this->site['base_url'], '/') . '/' . ltrim($path, '/');
    }
}
