<?php

declare(strict_types=1);

namespace Tests\Content;

use Blog\Content\ArticleIndexBuilder;
use Blog\Content\ArticleRepository;
use Blog\Content\FrontMatterParser;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ArticleIndexBuilderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/blog-content-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0775, true);

        file_put_contents($this->directory . '/primeiro.md', $this->article('Primeiro', '2026-10-17'));
        file_put_contents($this->directory . '/segundo.md', $this->article('Segundo', '2026-10-18'));
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->directory);
    }

    public function testItBuildsAnIndexOrderedByMostRecentArticle(): void
    {
        $builder = new ArticleIndexBuilder(new ArticleRepository($this->directory, new FrontMatterParser()));
        $index = $builder->build();

        self::assertSame(['segundo', 'primeiro'], array_column($index['articles'], 'slug'));
    }

    public function testItWritesValidJsonAtomically(): void
    {
        $builder = new ArticleIndexBuilder(new ArticleRepository($this->directory, new FrontMatterParser()));
        $destination = $this->directory . '/nested/article-index.json';
        $builder->write($destination, $builder->build());

        self::assertFileExists($destination);
        self::assertSame('segundo', json_decode((string) file_get_contents($destination), true, flags: JSON_THROW_ON_ERROR)['articles'][0]['slug']);
    }

    private function article(string $title, string $date): string
    {
        return <<<MARKDOWN
---
title: {$title}
date: {$date}
description: Descrição de {$title}
keywords:
  - php
tags:
  - arquitetura
---

Conteúdo
MARKDOWN;
    }
}
