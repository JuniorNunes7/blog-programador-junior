<?php

declare(strict_types=1);

namespace Blog\Content\Infrastructure;

use Blog\Content\Contract\ArticleRepository;
use Blog\Content\Errors\ContentException;
use Blog\Content\Model\Article;
use FilesystemIterator;

final readonly class MarkdownArticleRepository implements ArticleRepository
{
    public function __construct(
        private string $articlesDirectory,
        private FrontMatterParser $parser,
    ) {
    }

    /** @return list<Article> */
    public function all(): array
    {
        if (!is_dir($this->articlesDirectory)) {
            throw new ContentException(sprintf('O diretório de artigos não existe: %s', $this->articlesDirectory));
        }

        $articles = [];
        $files = new FilesystemIterator($this->articlesDirectory, FilesystemIterator::SKIP_DOTS);

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            $slug = $file->getBasename('.md');
            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                throw new ContentException(sprintf('Não foi possível ler o artigo "%s".', $slug));
            }

            $articles[] = $this->parser->parse($slug, $contents);
        }

        usort($articles, static function (Article $first, Article $second): int {
            $firstArticlePosition = [$first->metadata->publishedAt->format('Y-m-d'), $first->metadata->slug];
            $secondArticlePosition = [$second->metadata->publishedAt->format('Y-m-d'), $second->metadata->slug];

            return $secondArticlePosition <=> $firstArticlePosition;
        });

        return $articles;
    }

    public function find(string $slug): ?Article
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            return null;
        }

        $path = $this->articlesDirectory . DIRECTORY_SEPARATOR . $slug . '.md';
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new ContentException(sprintf('Não foi possível ler o artigo "%s".', $slug));
        }

        return $this->parser->parse($slug, $contents);
    }

    public function modifiedAt(string $slug): ?int
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            return null;
        }

        $modifiedAt = filemtime($this->articlesDirectory . DIRECTORY_SEPARATOR . $slug . '.md');

        return $modifiedAt === false ? null : $modifiedAt;
    }
}
