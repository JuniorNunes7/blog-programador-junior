<?php

declare(strict_types=1);

namespace Blog\Content\Index;

use Blog\Content\Errors\ContentException;

final class ArticleIndexWriter
{
    public function write(string $destination, ArticleIndex $index): void
    {
        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new ContentException(sprintf('Não foi possível criar o diretório do índice: %s', $directory));
        }

        try {
            $json = json_encode(
                $index,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
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
