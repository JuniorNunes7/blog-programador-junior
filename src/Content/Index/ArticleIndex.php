<?php

declare(strict_types=1);

namespace Blog\Content\Index;

use JsonSerializable;

final readonly class ArticleIndex implements JsonSerializable
{
    /** @param list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}> $articles */
    public function __construct(
        public string $generatedAt,
        public array $articles,
    ) {
    }

    /** @return array{generated_at: string, articles: list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}>} */
    public function jsonSerialize(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'articles' => $this->articles,
        ];
    }
}
