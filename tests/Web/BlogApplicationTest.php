<?php

declare(strict_types=1);

namespace Tests\Web;

use Blog\Content\Infrastructure\FrontMatterParser;
use Blog\Content\Infrastructure\MarkdownArticleRepository;
use Blog\Web\ArticleIndex;
use Blog\Web\BlogApplication;
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

    private function application(): BlogApplication
    {
        return new BlogApplication(
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
        );
    }
}
