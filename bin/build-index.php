#!/usr/bin/env php
<?php

declare(strict_types=1);

use Blog\Content\ArticleIndexBuilder;
use Blog\Content\ArticleRepository;
use Blog\Content\ContentException;
use Blog\Content\FrontMatterParser;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

try {
    $repository = new ArticleRepository($root . '/content/articles', new FrontMatterParser());
    $builder = new ArticleIndexBuilder($repository);
    $index = $builder->build();
    $builder->write($root . '/storage/article-index.json', $index);

    printf("Índice atualizado com %d artigo(s).%s", count($index['articles']), PHP_EOL);
} catch (ContentException $exception) {
    fwrite(STDERR, sprintf("Erro ao gerar o índice: %s%s", $exception->getMessage(), PHP_EOL));
    exit(1);
}
