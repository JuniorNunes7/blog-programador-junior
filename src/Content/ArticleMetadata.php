<?php

declare(strict_types=1);

namespace Blog\Content;

use DateTimeImmutable;

final readonly class ArticleMetadata
{
    /**
     * @param list<string> $keywords
     * @param list<string> $tags
     */
    public function __construct(
        public string $slug,
        public string $title,
        public DateTimeImmutable $publishedAt,
        public string $description,
        public array $keywords,
        public array $tags,
    ) {
    }

    /** @return array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>} */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'date' => $this->publishedAt->format('Y-m-d'),
            'description' => $this->description,
            'keywords' => $this->keywords,
            'tags' => $this->tags,
        ];
    }
}
