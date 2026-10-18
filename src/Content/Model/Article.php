<?php

declare(strict_types=1);

namespace Blog\Content\Model;

final readonly class Article
{
    public function __construct(
        public ArticleMetadata $metadata,
        public string $markdown,
    ) {
    }
}
