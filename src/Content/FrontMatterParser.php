<?php

declare(strict_types=1);

namespace Blog\Content;

use DateTimeImmutable;

final class FrontMatterParser
{
    private const REQUIRED_FIELDS = ['title', 'date', 'description', 'keywords', 'tags'];

    public function parse(string $slug, string $document): Article
    {
        $this->assertSlug($slug);

        if (preg_match('/\A---\R(?<frontMatter>.*?)\R---\R?(?<markdown>.*)\z/s', $document, $matches) !== 1) {
            throw new ContentException(sprintf('O artigo "%s" precisa começar com front matter YAML delimitado por ---.', $slug));
        }

        $frontMatter = $this->parseYamlSubset($matches['frontMatter']);
        $this->assertRequiredFields($slug, $frontMatter);

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $frontMatter['date']);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $frontMatter['date']) {
            throw new ContentException(sprintf('O campo "date" do artigo "%s" deve usar o formato YYYY-MM-DD.', $slug));
        }

        return new Article(
            new ArticleMetadata(
                $slug,
                $frontMatter['title'],
                $date,
                $frontMatter['description'],
                $frontMatter['keywords'],
                $frontMatter['tags'],
            ),
            trim($matches['markdown']),
        );
    }

    /** @return array<string, string|list<string>> */
    private function parseYamlSubset(string $frontMatter): array
    {
        $fields = [];
        $activeList = null;

        foreach (preg_split('/\R/', $frontMatter) as $lineNumber => $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            if (preg_match('/^(?<key>[a-z_]+):\s*(?<value>.*)$/', $line, $matches) === 1) {
                $key = $matches['key'];
                $value = trim($matches['value']);
                $activeList = null;

                if ($value === '') {
                    $fields[$key] = [];
                    $activeList = $key;
                    continue;
                }

                $fields[$key] = $this->unquote($value);
                continue;
            }

            if ($activeList !== null && preg_match('/^\s+-\s+(?<value>.+)$/', $line, $matches) === 1) {
                $fields[$activeList][] = $this->unquote(trim($matches['value']));
                continue;
            }

            throw new ContentException(sprintf('Front matter inválido na linha %d.', $lineNumber + 1));
        }

        return $fields;
    }

    /** @param array<string, string|list<string>> $frontMatter */
    private function assertRequiredFields(string $slug, array $frontMatter): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $frontMatter)) {
                throw new ContentException(sprintf('O artigo "%s" não possui o campo obrigatório "%s".', $slug, $field));
            }

            $value = $frontMatter[$field];
            if (is_string($value) && trim($value) !== '') {
                continue;
            }

            if (is_array($value) && $value !== [] && array_all($value, static fn (string $item): bool => trim($item) !== '')) {
                continue;
            }

            throw new ContentException(sprintf('O campo "%s" do artigo "%s" não pode ficar vazio.', $field, $slug));
        }

        foreach (['title', 'date', 'description'] as $field) {
            if (!is_string($frontMatter[$field])) {
                throw new ContentException(sprintf('O campo "%s" do artigo "%s" deve ser um texto.', $field, $slug));
            }
        }

        foreach (['keywords', 'tags'] as $field) {
            if (!is_array($frontMatter[$field])) {
                throw new ContentException(sprintf('O campo "%s" do artigo "%s" deve ser uma lista.', $field, $slug));
            }
        }
    }

    private function assertSlug(string $slug): void
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new ContentException(sprintf('O slug "%s" deve usar letras minúsculas, números e hífens.', $slug));
        }
    }

    private function unquote(string $value): string
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
