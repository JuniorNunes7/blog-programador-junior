<?php

declare(strict_types=1);

namespace Tests\Web;

use Blog\Content\Infrastructure\FrontMatterParser;
use Blog\Content\Infrastructure\MarkdownArticleRepository;
use Blog\Web\ArticleIndex;
use Blog\Web\ArticleRenderer;
use Blog\Web\BlogApplication;
use Blog\Web\BlogController;
use Blog\Web\TemplateRenderer;
use League\CommonMark\CommonMarkConverter;
use PHPUnit\Framework\TestCase;

final class BlogApplicationTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/blog-web-' . bin2hex(random_bytes(8));
        mkdir($this->directory . '/articles', 0775, true);
        file_put_contents($this->directory . '/articles/um-artigo.md', <<<'MARKDOWN'
---
title: Um artigo
date: 2026-10-18
description: Descrição do artigo
keywords:
  - php
tags:
  - PHP
---

# Olá

<script>alert('não renderizar')</script>
MARKDOWN);
        file_put_contents($this->directory . '/index.json', json_encode([
            'articles' => [[
                'slug' => 'um-artigo',
                'title' => 'Um artigo',
                'date' => '2026-10-18',
                'description' => 'Descrição do artigo',
                'keywords' => ['php'],
                'tags' => ['PHP'],
            ]],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/articles/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory . '/articles');
        unlink($this->directory . '/index.json');
        rmdir($this->directory);
    }

    public function testItRendersTheArticleUsingSafeMarkdown(): void
    {
        $response = $this->application()->handle('GET', '/artigos/um-artigo');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('<h2>Olá</h2>', $response->body);
        self::assertSame(1, substr_count($response->body, '<h1'));
        self::assertStringNotContainsString('<script>', $response->body);
        self::assertStringContainsString(
            '<link rel="canonical" href="https://example.test/artigos/um-artigo">',
            $response->body,
        );
        self::assertStringContainsString('"@type":"BlogPosting"', $response->body);
        self::assertArrayHasKey('Last-Modified', $response->headers);
    }

    public function testItListsArticlesByTagWithoutReadingTheArticleMarkdown(): void
    {
        $response = $this->application()->handle('GET', '/tags/php');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('Artigos sobre PHP', $response->body);
        self::assertStringContainsString(
            '<link rel="canonical" href="https://example.test/tags/php">',
            $response->body,
        );
    }

    public function testItListsEveryTagWithCountsAndAStableCanonicalUrl(): void
    {
        $response = $this->application()->handle('GET', '/tags');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('<h1>Todos os assuntos</h1>', $response->body);
        self::assertStringContainsString('PHP</span><span class="topic-count">1 artigo</span>', $response->body);
        self::assertStringContainsString(
            '<link rel="canonical" href="https://example.test/tags">',
            $response->body,
        );
    }

    public function testItPrioritizesFrequentTagsAndPreservesAnActiveTagOutsideTheLimit(): void
    {
        $path = $this->directory . '/index.json';
        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        foreach (['PHP', 'Arquitetura', 'Testes', 'Carreira', 'Docker', 'Linux', 'Laravel'] as $number => $tag) {
            $data['articles'][] = [
                'slug' => 'artigo-' . $number,
                'title' => 'Artigo ' . $number,
                'date' => '2026-10-17',
                'description' => 'Descrição',
                'keywords' => ['php'],
                'tags' => [$tag],
            ];
        }
        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));

        $index = ArticleIndex::fromFile($path);

        self::assertSame(
            ['php', 'arquitetura', 'carreira', 'docker', 'laravel', 'linux'],
            array_keys($index->featuredTags()),
        );
        self::assertStringContainsString('>Testes</a>', $this->application()->handle('GET', '/tags/testes')->body);
    }

    public function testItReturnsNotFoundForUnknownRoutes(): void
    {
        $response = $this->application()->handle('GET', '/artigos/nao-existe');

        self::assertSame(404, $response->status);
        self::assertStringContainsString('content="noindex, follow"', $response->body);
        self::assertStringNotContainsString('rel="canonical"', $response->body);
    }

    public function testItGeneratesSitemapRssAndRobotsFromTheIndex(): void
    {
        $sitemap = $this->application()->handle('GET', '/sitemap.xml');
        $feed = $this->application()->handle('GET', '/feed.xml');
        $robots = $this->application()->handle('GET', '/robots.txt');

        self::assertSame('application/xml; charset=UTF-8', $sitemap->headers['Content-Type']);
        self::assertStringContainsString(
            '<loc>https://example.test/artigos/um-artigo</loc>',
            $sitemap->body,
        );
        self::assertStringContainsString('<loc>https://example.test/tags</loc>', $sitemap->body);
        self::assertStringContainsString(
            '<guid isPermaLink="true">https://example.test/artigos/um-artigo</guid>',
            $feed->body,
        );
        self::assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $robots->body);
    }

    public function testItSuppressesOnlyTheMatchingLeadingMarkdownTitle(): void
    {
        $path = $this->directory . '/articles/um-artigo.md';
        $source = file_get_contents($path);
        file_put_contents($path, str_replace('# Olá', "# Um **artigo**\n\n# Uma seção\n\n## Outra seção", $source));

        $body = $this->application()->handle('GET', '/artigos/um-artigo')->body;

        self::assertSame(1, substr_count($body, '<h1'));
        self::assertStringNotContainsString('<h2>Um <strong>artigo</strong></h2>', $body);
        self::assertStringContainsString('<h2>Uma seção</h2>', $body);
        self::assertStringContainsString('<h2>Outra seção</h2>', $body);
    }

    public function testPaginatedListingsHaveTheirOwnCanonicalAndTitle(): void
    {
        $path = $this->directory . '/index.json';
        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $data['articles'] = array_fill(0, 11, $data['articles'][0]);
        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));

        foreach (['/pagina/2', '/tags/php/pagina/2'] as $route) {
            $body = $this->application()->handle('GET', $route)->body;
            self::assertStringContainsString('href="https://example.test' . $route . '"', $body);
            self::assertStringContainsString('content="https://example.test' . $route . '"', $body);
            self::assertStringContainsString('— Página 2 · Blog teste</title>', $body);
        }
        self::assertSame(404, $this->application()->handle('GET', '/pagina/3')->status);
    }

    public function testStructuredDataPreservesTextAndCannotCloseItsScriptElement(): void
    {
        $path = $this->directory . '/articles/um-artigo.md';
        $title = 'Código & dados </script> seguros';
        file_put_contents($path, str_replace('title: Um artigo', 'title: ' . $title, file_get_contents($path)));
        $body = $this->application()->handle('GET', '/artigos/um-artigo')->body;
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $body, $matches);
        $schema = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        self::assertSame($title, $schema['headline']);
        self::assertSame('https://example.test/assets/brand/banner-light.png', $schema['image']);
        self::assertStringNotContainsString('</script>', $matches[1]);
        self::assertStringContainsString('name="twitter:card" content="summary_large_image"', $body);
        self::assertStringContainsString('property="og:image:width" content="1200"', $body);
    }

    public function testEtagIdentifiesTheRenderedPageIncludingMetadata(): void
    {
        $first = $this->application()->handle('GET', '/artigos/um-artigo');
        self::assertSame('"' . hash('sha256', $first->body) . '"', $first->headers['ETag']);

        $second = $this->application(['author' => 'Outro autor'])->handle('GET', '/artigos/um-artigo');

        self::assertNotSame($first->headers['ETag'], $second->headers['ETag']);
    }

    public function testItRendersOnlyConfiguredSocialLinks(): void
    {
        $response = $this->application(['social' => [
            'linkedin' => 'https://www.linkedin.com/in/autor',
            'github' => null,
            'instagram' => 'https://www.instagram.com/autor',
        ]])->handle('GET', '/');

        self::assertStringContainsString('href="https://www.linkedin.com/in/autor"', $response->body);
        self::assertStringContainsString('href="https://www.instagram.com/autor"', $response->body);
        self::assertStringNotContainsString('GitHub de Autor', $response->body);
    }

    public function testArticleImagesAndScrollableBlocksAreAccessibleWithoutJavascript(): void
    {
        $path = $this->directory . '/articles/um-artigo.md';
        file_put_contents($path, file_get_contents($path) . <<<'MARKDOWN'

```php
echo '<script>';
```

| Campo | Valor |
| --- | --- |
| Teste | Longo |

![Papel](/assets/images/editorial-paper-640.webp)

[Link inseguro](javascript:alert(1))
MARKDOWN);

        $body = $this->application()->handle('GET', '/artigos/um-artigo')->body;
        self::assertStringContainsString('<pre tabindex="0" role="region" aria-label="Bloco de código">', $body);
        self::assertStringContainsString('aria-label="Tabela" class="table-scroll"', $body);
        self::assertStringContainsString('loading="lazy" decoding="async" width="640" height="720"', $body);
        self::assertStringNotContainsString('href="javascript:', $body);
        self::assertStringContainsString('&lt;script&gt;', $body);
    }

    /** @param array<string, mixed> $overrides */
    private function application(array $overrides = []): BlogApplication
    {
        $site = array_replace([
            'name' => 'Blog teste',
            'description' => 'Descrição do blog',
            'author' => 'Autor',
            'base_url' => 'https://example.test',
            'articles_per_page' => 10,
            'social' => ['linkedin' => null, 'github' => null, 'instagram' => null],
        ], $overrides);

        return new BlogApplication(
            new BlogController(
                $site,
                ArticleIndex::fromFile($this->directory . '/index.json'),
                new MarkdownArticleRepository($this->directory . '/articles', new FrontMatterParser()),
                new ArticleRenderer(
                    new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]),
                    dirname(__DIR__, 2) . '/public',
                ),
                new TemplateRenderer(dirname(__DIR__, 2) . '/templates', $site),
            ),
        );
    }
}
