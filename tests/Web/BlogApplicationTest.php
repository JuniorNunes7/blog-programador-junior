<?php

declare(strict_types=1);

namespace Tests\Web;

use Blog\Content\Infrastructure\FrontMatterParser;
use Blog\Content\Infrastructure\MarkdownArticleRepository;
use Blog\Web\ArticleIndex;
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
        self::assertStringContainsString('<h1>Olá</h1>', $response->body);
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
    }

    public function testItReturnsNotFoundForUnknownRoutes(): void
    {
        $response = $this->application()->handle('GET', '/artigos/nao-existe');

        self::assertSame(404, $response->status);
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
        self::assertStringContainsString(
            '<guid isPermaLink="true">https://example.test/artigos/um-artigo</guid>',
            $feed->body,
        );
        self::assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $robots->body);
    }

    private function application(): BlogApplication
    {
        return new BlogApplication(
            new BlogController(
                [
                    'name' => 'Blog teste',
                    'description' => 'Descrição do blog',
                    'author' => 'Autor',
                    'base_url' => 'https://example.test',
                    'articles_per_page' => 10,
                ],
                ArticleIndex::fromFile($this->directory . '/index.json'),
                new MarkdownArticleRepository($this->directory . '/articles', new FrontMatterParser()),
                new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]),
                new TemplateRenderer(dirname(__DIR__, 2) . '/templates', [
                    'name' => 'Blog teste',
                    'description' => 'Descrição do blog',
                    'author' => 'Autor',
                    'base_url' => 'https://example.test',
                    'articles_per_page' => 10,
                ]),
            ),
        );
    }
}
