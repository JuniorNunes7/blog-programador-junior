#!/usr/bin/env php
<?php

declare(strict_types=1);

use Blog\Content\Errors\ContentException;
use Blog\Content\Index\ArticleIndexBuilder;
use Blog\Content\Index\ArticleIndexWriter;
use Blog\Content\Infrastructure\FrontMatterParser;
use Blog\Content\Infrastructure\MarkdownArticleRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

try {
    $repository = new MarkdownArticleRepository($root . '/content/articles', new FrontMatterParser());
    $builder = new ArticleIndexBuilder($repository);
    $index = $builder->build();
    (new ArticleIndexWriter())->write($root . '/storage/article-index.json', $index);

    printf("Índice atualizado com %d artigo(s).%s", count($index->articles), PHP_EOL);
} catch (ContentException $exception) {
    fwrite(STDERR, sprintf("Erro ao gerar o índice: %s%s", $exception->getMessage(), PHP_EOL));
    exit(1);
}
