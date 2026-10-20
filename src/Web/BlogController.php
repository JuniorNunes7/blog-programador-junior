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

        $headers = ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'public, max-age=300'];
        $modifiedAt = $this->articles->modifiedAt($slug);
        if ($modifiedAt !== null) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $modifiedAt) . ' GMT';
            $headers['ETag'] = '"' . sha1($article->markdown) . '"';
        }

        return new Response(200, $this->templates->page($article->metadata->title, 'pages/article', [
            'article' => $article,
            'articleHtml' => $this->markdown->convert($article->markdown)->getContent(),
            'description' => $article->metadata->description,
            'path' => '/artigos/' . $slug,
        ]), $headers);
    }

    public function notFound(): Response
    {
        return new Response(404, $this->templates->page('Página não encontrada', 'pages/not-found'));
    }

    public function sitemap(): Response
    {
        $urls = [sprintf('<url><loc>%s</loc></url>', $this->xml($this->absoluteUrl('/')))];
        foreach ($this->index->articles() as $article) {
            $urls[] = sprintf(
                '<url><loc>%s</loc><lastmod>%s</lastmod></url>',
                $this->xml($this->absoluteUrl('/artigos/' . $article['slug'])),
                $this->xml($article['date']),
            );
        }
        foreach (array_keys($this->index->tags()) as $tag) {
            $urls[] = sprintf('<url><loc>%s</loc></url>', $this->xml($this->absoluteUrl('/tags/' . $tag)));
        }

        $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . implode("\n", $urls)
            . "\n</urlset>\n";

        return new Response(200, $body, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function feed(): Response
    {
        $items = array_map(function (array $article): string {
            $date = new \DateTimeImmutable($article['date'] . 'T00:00:00+00:00');
            $url = $this->absoluteUrl('/artigos/' . $article['slug']);

            return sprintf(
                '<item><title>%s</title><link>%s</link>'
                    . '<guid isPermaLink="true">%s</guid><description>%s</description>'
                    . '<pubDate>%s</pubDate></item>',
                $this->xml($article['title']),
                $this->xml($url),
                $this->xml($url),
                $this->xml($article['description']),
                $date->format(DATE_RSS),
            );
        }, $this->index->articles());

        $body = sprintf(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . '<rss version="2.0"><channel><title>%s</title><link>%s</link>'
                . '<description>%s</description><language>pt-BR</language>%s</channel></rss>'
                . "\n",
            $this->xml($this->site['name']),
            $this->xml($this->absoluteUrl('/')),
            $this->xml($this->site['description']),
            implode('', $items),
        );

        return new Response(200, $body, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots(): Response
    {
        return new Response(200, "User-agent: *\nAllow: /\nSitemap: " . $this->absoluteUrl('/sitemap.xml') . "\n", [
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

    private function absoluteUrl(string $path): string
    {
        return rtrim($this->site['base_url'], '/') . '/' . ltrim($path, '/');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
