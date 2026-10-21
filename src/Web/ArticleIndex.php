<?php

declare(strict_types=1);

namespace Blog\Web;

use Blog\Content\Errors\ContentException;

final readonly class ArticleIndex
{
    /** @param list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}> $articles */
    private function __construct(private array $articles)
    {
    }

    public static function fromFile(string $path): self
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new ContentException(
                'O índice de artigos não existe. Execute php bin/build-index.php antes de publicar.',
            );
        }

        try {
            $index = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ContentException('O índice de artigos contém JSON inválido.', previous: $exception);
        }

        if (!is_array($index['articles'] ?? null)) {
            throw new ContentException('O índice de artigos não possui uma lista válida.');
        }

        return new self($index['articles']);
    }

    /** @return list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}> */
    public function articles(): array
    {
        return $this->articles;
    }

    /** @return array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}|null */
    public function find(string $slug): ?array
    {
        foreach ($this->articles as $article) {
            if ($article['slug'] === $slug) {
                return $article;
            }
        }

        return null;
    }

    /** @return array<string, array{name: string, count: int}> */
    public function tags(): array
    {
        $tags = [];

        foreach ($this->articles as $article) {
            foreach ($article['tags'] as $tag) {
                $slug = TagSlugger::slug($tag);
                $tags[$slug] ??= ['name' => $tag, 'count' => 0];
                $tags[$slug]['count']++;
            }
        }

        ksort($tags);

        return $tags;
    }

    /** @return array<string, array{name: string, count: int}> */
    public function featuredTags(int $limit = 6): array
    {
        $tags = $this->tags();

        uasort($tags, static function (array $first, array $second): int {
            $byCount = $second['count'] <=> $first['count'];

            return $byCount !== 0 ? $byCount : strcasecmp($first['name'], $second['name']);
        });

        return array_slice($tags, 0, $limit, true);
    }

    /** @return list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}> */
    public function forTag(string $tagSlug): array
    {
        return array_values(array_filter(
            $this->articles,
            static fn (array $article): bool => in_array(
                $tagSlug,
                array_map(TagSlugger::slug(...), $article['tags']),
                true,
            ),
        ));
    }
}
