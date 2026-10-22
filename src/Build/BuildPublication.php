<?php

declare(strict_types=1);

namespace Blog\Build;

use RuntimeException;
use Throwable;

final readonly class BuildPublication
{
    public function __construct(private string $output)
    {
    }

    public function recover(): void
    {
        $backup = $this->output . '.previous';
        if (!file_exists($this->output) && is_dir($backup)) {
            if (!@rename($backup, $this->output)) {
                throw new RuntimeException('Não foi possível recuperar o build anterior.');
            }
        } elseif (is_dir($backup)) {
            BuildFiles::remove($backup);
        }
    }

    public function publish(string $staging, string $temporaryManifest, string $manifest): void
    {
        $backup = $this->output . '.previous';
        $hadOutput = is_dir($this->output);
        if ($hadOutput && !@rename($this->output, $backup)) {
            throw new RuntimeException('Não foi possível preservar o build anterior.');
        }
        try {
            if (!@rename($staging, $this->output)) {
                throw new RuntimeException('Não foi possível publicar o site gerado.');
            }
            if (!@rename($temporaryManifest, $manifest)) {
                throw new RuntimeException('Não foi possível publicar o manifesto.');
            }
        } catch (Throwable $exception) {
            // Only remove a new directory; a conflicting file may belong to the caller.
            if (is_dir($this->output)) {
                BuildFiles::remove($this->output);
            }
            if ($hadOutput && !@rename($backup, $this->output)) {
                throw new RuntimeException('Recupere o build preservado em ' . $backup, previous: $exception);
            }
            throw $exception;
        }
        BuildFiles::remove($backup);
    }
}
