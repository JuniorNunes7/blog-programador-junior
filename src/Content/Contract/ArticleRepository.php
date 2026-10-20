<?php

declare(strict_types=1);

namespace Blog\Content\Contract;

use Blog\Content\Model\Article;

interface ArticleRepository
{
    /** @return list<Article> */
    public function all(): array;

    public function find(string $slug): ?Article;
}
