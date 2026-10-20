<?php

declare(strict_types=1);

namespace Blog\Content\Index;

use Blog\Content\Contract\ArticleRepository;
use Blog\Content\Model\Article;

final readonly class ArticleIndexBuilder
{
    public function __construct(private ArticleRepository $repository)
    {
    }

    public function build(): ArticleIndex
    {
        return new ArticleIndex(
            gmdate('c'),
            array_map(static fn (Article $article): array => $article->metadata->toArray(), $this->repository->all()),
        );
    }
}
