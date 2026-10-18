<?php

declare(strict_types=1);

namespace Blog\Content;

final readonly class ArticleIndexBuilder
{
    public function __construct(private ArticleRepository $repository)
    {
    }

    /** @return array{generated_at: string, articles: list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}>} */
    public function build(): array
    {
        return [
            'generated_at' => gmdate('c'),
            'articles' => array_map(static fn (Article $article): array => $article->metadata->toArray(), $this->repository->all()),
        ];
    }

    /** @param array{generated_at: string, articles: list<array{slug: string, title: string, date: string, description: string, keywords: list<string>, tags: list<string>}>} $index */
    public function write(string $destination, array $index): void
    {
        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new ContentException(sprintf('Não foi possível criar o diretório do índice: %s', $directory));
        }

        try {
            $json = json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ContentException('Não foi possível serializar o índice de artigos.', previous: $exception);
        }

        $temporaryFile = tempnam($directory, 'article-index-');
        if ($temporaryFile === false || file_put_contents($temporaryFile, $json . PHP_EOL, LOCK_EX) === false) {
            throw new ContentException('Não foi possível gravar o índice temporário de artigos.');
        }

        if (!rename($temporaryFile, $destination)) {
            @unlink($temporaryFile);
            throw new ContentException('Não foi possível publicar o índice de artigos.');
        }
    }
}
