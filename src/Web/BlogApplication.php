<?php

declare(strict_types=1);

namespace Blog\Web;

use Blog\Content\ArticleMetadata;
use Blog\Content\ArticleRepository;
use League\CommonMark\CommonMarkConverter;

final readonly class BlogApplication
{
    /** @param array{name: string, description: string, author: string, base_url: string, articles_per_page: int} $site */
    public function __construct(
        private array $site,
        private ArticleIndex $index,
        private ArticleRepository $articles,
        private CommonMarkConverter $markdown,
    ) {
    }

    public function handle(string $method, string $path): Response
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $this->notFound();
        }

        $path = '/' . trim(rawurldecode(parse_url($path, PHP_URL_PATH) ?: '/'), '/');

        if ($path === '/') {
            return $this->listing($this->index->articles(), 'Artigos recentes', 1, '/');
        }

        if (preg_match('#^/pagina/(?<page>[1-9][0-9]*)$#', $path, $matches) === 1) {
            return $this->listing($this->index->articles(), 'Artigos recentes', (int) $matches['page'], '/');
        }

        if (preg_match('#^/artigos/(?<slug>[a-z0-9]+(?:-[a-z0-9]+)*)$#', $path, $matches) === 1) {
            return $this->article($matches['slug']);
        }

        if ($path === '/sitemap.xml') {
            return $this->sitemap();
        }

        if ($path === '/feed.xml') {
            return $this->feed();
        }

        if ($path === '/robots.txt') {
            return $this->robots();
        }

        if (preg_match('#^/tags/(?<tag>[a-z0-9]+(?:-[a-z0-9]+)*)(?:/pagina/(?<page>[1-9][0-9]*))?$#', $path, $matches) === 1) {
            $tag = $this->index->tags()[$matches['tag']] ?? null;
            if ($tag === null) {
                return $this->notFound();
            }

            return $this->listing(
                $this->index->forTag($matches['tag']),
                sprintf('Artigos sobre %s', $tag['name']),
                isset($matches['page']) ? (int) $matches['page'] : 1,
                '/tags/' . $matches['tag'],
            );
        }

        return $this->notFound();
    }

    /** @param list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}> $articles */
    private function listing(array $articles, string $heading, int $page, string $basePath): Response
    {
        $perPage = $this->site['articles_per_page'];
        $totalPages = max(1, (int) ceil(count($articles) / $perPage));
        if ($page > $totalPages) {
            return $this->notFound();
        }

        $visibleArticles = array_slice($articles, ($page - 1) * $perPage, $perPage);
        $content = sprintf('<section class="intro"><p class="eyebrow">Conhecimento compartilhado</p><h1>%s</h1><p>%s</p></section>', $this->escape($heading), $this->escape($this->site['description']));

        if ($visibleArticles === []) {
            $content .= '<p class="empty-state">Ainda não há artigos publicados.</p>';
        } else {
            $content .= '<section class="article-list" aria-label="Lista de artigos">';
            foreach ($visibleArticles as $article) {
                $content .= $this->articleCard($article);
            }
            $content .= '</section>';
        }

        $content .= $this->pagination($page, $totalPages, $basePath);

        $path = $page === 1 ? $basePath : $this->pageUrl($basePath, $page);

        return new Response(200, $this->layout($heading, $this->site['description'], $content, $path));
    }

    private function article(string $slug): Response
    {
        $metadata = $this->index->find($slug);
        $article = $metadata === null ? null : $this->articles->find($slug);

        if ($article === null) {
            return $this->notFound();
        }

        $content = sprintf(
            '<article class="post"><a class="back-link" href="/">← Todos os artigos</a><header><p class="eyebrow">%s</p><h1>%s</h1><p class="post-description">%s</p>%s</header><div class="prose">%s</div></article>',
            $this->escape($article->metadata->publishedAt->format('d/m/Y')),
            $this->escape($article->metadata->title),
            $this->escape($article->metadata->description),
            $this->tagLinks($article->metadata->tags),
            $this->markdown->convert($article->markdown)->getContent(),
        );

        $headers = ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'public, max-age=300'];
        $modifiedAt = $this->articles->modifiedAt($slug);
        if ($modifiedAt !== null) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $modifiedAt) . ' GMT';
            $headers['ETag'] = '"' . sha1($article->markdown) . '"';
        }

        return new Response(200, $this->layout($article->metadata->title, $article->metadata->description, $content, '/artigos/' . $slug, $article->metadata), $headers);
    }

    private function notFound(): Response
    {
        $content = '<section class="not-found"><p class="eyebrow">404</p><h1>Esta página não existe.</h1><p>Talvez o artigo tenha mudado de endereço ou ainda não tenha sido publicado.</p><a class="button" href="/">Voltar para os artigos</a></section>';

        return new Response(404, $this->layout('Página não encontrada', 'O endereço solicitado não está disponível.', $content, '/404'));
    }

    private function sitemap(): Response
    {
        $urls = [sprintf('<url><loc>%s</loc></url>', $this->xml($this->absoluteUrl('/')))];
        foreach ($this->index->articles() as $article) {
            $urls[] = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod></url>', $this->xml($this->absoluteUrl('/artigos/' . $article['slug'])), $this->xml($article['date']));
        }
        foreach (array_keys($this->index->tags()) as $tag) {
            $urls[] = sprintf('<url><loc>%s</loc></url>', $this->xml($this->absoluteUrl('/tags/' . $tag)));
        }

        return new Response(200, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n" . implode("\n", $urls) . "\n</urlset>\n", [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function feed(): Response
    {
        $items = array_map(function (array $article): string {
            $date = new \DateTimeImmutable($article['date'] . 'T00:00:00+00:00');
            $url = $this->absoluteUrl('/artigos/' . $article['slug']);

            return sprintf('<item><title>%s</title><link>%s</link><guid isPermaLink="true">%s</guid><description>%s</description><pubDate>%s</pubDate></item>', $this->xml($article['title']), $this->xml($url), $this->xml($url), $this->xml($article['description']), $date->format(DATE_RSS));
        }, $this->index->articles());

        $body = sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\"><channel><title>%s</title><link>%s</link><description>%s</description><language>pt-BR</language>%s</channel></rss>\n", $this->xml($this->site['name']), $this->xml($this->absoluteUrl('/')), $this->xml($this->site['description']), implode('', $items));

        return new Response(200, $body, ['Content-Type' => 'application/rss+xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    private function robots(): Response
    {
        return new Response(200, "User-agent: *\nAllow: /\nSitemap: " . $this->absoluteUrl('/sitemap.xml') . "\n", ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    /** @param array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>} $article */
    private function articleCard(array $article): string
    {
        return sprintf(
            '<article class="article-card"><p class="eyebrow">%s</p><h2><a href="/artigos/%s">%s</a></h2><p>%s</p>%s</article>',
            $this->escape($article['date']),
            rawurlencode($article['slug']),
            $this->escape($article['title']),
            $this->escape($article['description']),
            $this->tagLinks($article['tags']),
        );
    }

    /** @param list<string> $tags */
    private function tagLinks(array $tags): string
    {
        $links = array_map(fn (string $tag): string => sprintf('<a class="tag" href="/tags/%s">%s</a>', rawurlencode(TagSlugger::slug($tag)), $this->escape($tag)), $tags);

        return $links === [] ? '' : '<p class="tags">' . implode('', $links) . '</p>';
    }

    private function pagination(int $page, int $totalPages, string $basePath): string
    {
        if ($totalPages === 1) {
            return '';
        }

        $previous = $page > 1 ? sprintf('<a href="%s">← Mais recentes</a>', $this->pageUrl($basePath, $page - 1)) : '<span></span>';
        $next = $page < $totalPages ? sprintf('<a href="%s">Mais antigos →</a>', $this->pageUrl($basePath, $page + 1)) : '<span></span>';

        return sprintf('<nav class="pagination" aria-label="Paginação">%s<span>Página %d de %d</span>%s</nav>', $previous, $page, $totalPages, $next);
    }

    private function pageUrl(string $basePath, int $page): string
    {
        if ($page === 1) {
            return $basePath;
        }

        return $basePath === '/' ? '/pagina/' . $page : $basePath . '/pagina/' . $page;
    }

    private function layout(string $title, string $description, string $content, string $path, ?ArticleMetadata $article = null): string
    {
        $siteName = $this->escape($this->site['name']);
        $fullTitle = $this->escape($title . ' · ' . $this->site['name']);
        $description = $this->escape($description);
        $canonicalUrl = $this->absoluteUrl($path);
        $schema = $article === null
            ? ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $this->site['name'], 'url' => $this->absoluteUrl('/')]
            : ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $article->title, 'description' => $article->description, 'datePublished' => $article->publishedAt->format('Y-m-d'), 'dateModified' => $article->publishedAt->format('Y-m-d'), 'mainEntityOfPage' => $canonicalUrl, 'author' => ['@type' => 'Person', 'name' => $this->site['author']], 'publisher' => ['@type' => 'Person', 'name' => $this->site['author']]];
        $schemaJson = htmlspecialchars((string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $keywords = $article === null ? '' : '<meta name="keywords" content="' . $this->escape(implode(', ', $article->keywords)) . '">';
        $articleMeta = $article === null ? '' : '<meta property="article:published_time" content="' . $this->escape($article->publishedAt->format(DATE_ATOM)) . '">';
        $ogType = $article === null ? 'website' : 'article';

        return <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$fullTitle}</title>
    <meta name="description" content="{$description}">
    {$keywords}
    <link rel="canonical" href="{$this->escape($canonicalUrl)}">
    <meta property="og:type" content="{$ogType}">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="{$siteName}">
    <meta property="og:title" content="{$fullTitle}">
    <meta property="og:description" content="{$description}">
    <meta property="og:url" content="{$this->escape($canonicalUrl)}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{$fullTitle}">
    <meta name="twitter:description" content="{$description}">
    {$articleMeta}
    <script type="application/ld+json">{$schemaJson}</script>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <header class="site-header"><div class="site-shell"><a class="brand" href="/">{$siteName}</a><nav aria-label="Principal"><a href="/">Artigos</a></nav></div></header>
    <main id="conteudo" class="site-shell">{$content}</main>
    <footer class="site-footer"><div class="site-shell">Escrito por {$this->escape($this->site['author'])}.</div></footer>
</body>
</html>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function absoluteUrl(string $path): string
    {
        return rtrim($this->site['base_url'], '/') . '/' . ltrim($path, '/');
    }
}
