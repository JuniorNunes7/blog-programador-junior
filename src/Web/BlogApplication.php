<?php

declare(strict_types=1);

namespace Blog\Web;

use Blog\Content\Contract\ArticleRepository;
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

        return new Response(200, $this->layout($heading, $content));
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

        return new Response(200, $this->layout($article->metadata->title, $content));
    }

    private function notFound(): Response
    {
        $content = '<section class="not-found"><p class="eyebrow">404</p><h1>Esta página não existe.</h1><p>Talvez o artigo tenha mudado de endereço ou ainda não tenha sido publicado.</p><a class="button" href="/">Voltar para os artigos</a></section>';

        return new Response(404, $this->layout('Página não encontrada', $content));
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

    private function layout(string $title, string $content): string
    {
        $siteName = $this->escape($this->site['name']);
        $fullTitle = $this->escape($title . ' · ' . $this->site['name']);

        return <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$fullTitle}</title>
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
}
