<?php

declare(strict_types=1);

namespace Blog\Build;

use Blog\Content\Infrastructure\FrontMatterParser;
use Blog\Content\Infrastructure\MarkdownArticleRepository;
use Blog\Web\ArticleIndex;
use Blog\Web\ArticleRenderer;
use Blog\Web\BlogApplication;
use Blog\Web\BlogController;
use Blog\Web\TemplateRenderer;
use FilesystemIterator;
use League\CommonMark\CommonMarkConverter;
use RuntimeException;

final readonly class SiteBuilder
{
    private string $articlesDirectory;
    private string $output;
    private string $storage;

    /** @param array<string, mixed> $site */
    public function __construct(
        private string $root,
        private array $site,
        ?string $articlesDirectory = null,
        ?string $output = null,
        ?string $storage = null,
    ) {
        $this->articlesDirectory = $articlesDirectory ?? $root . '/content/articles';
        $this->output = $output ?? $root . '/dist';
        $this->storage = $storage ?? $root . '/storage';
    }

    /** @return array{generated: int, reused: int, removed: int, unchanged: bool} */
    public function build(bool $full = false): array
    {
        BuildFiles::directory($this->storage);
        $lock = @fopen($this->storage . '/build.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Não foi possível abrir o lock do build.');
        }
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Já existe um build em execução.');
            }
            $publication = new BuildPublication($this->output);
            $publication->recover();

            return $this->generate($publication, $full);
        } finally {
            fclose($lock);
        }
    }

    /** @return array{generated: int, reused: int, removed: int, unchanged: bool} */
    private function generate(BuildPublication $publication, bool $full): array
    {
        $manifestPath = $this->storage . '/build-manifest.json';
        $previous = BuildManifest::read($manifestPath);
        $global = $this->globalFingerprint();
        $full = $full || $previous === null || $previous['global'] !== $global || !$this->outputIsIntact($previous);
        $hashes = $this->articleHashes();
        $oldArticles = $previous['articles'] ?? [];
        $removed = count(array_diff_key($oldArticles, $hashes));
        $repository = new MarkdownArticleRepository($this->articlesDirectory, new FrontMatterParser());
        $articles = [];
        $changed = [];
        foreach ($hashes as $slug => $hash) {
            if (!$full && ($oldArticles[$slug]['hash'] ?? null) === $hash) {
                $articles[$slug] = $oldArticles[$slug];
                continue;
            }
            $article = $repository->find((string) $slug);
            if ($article === null) {
                throw new RuntimeException('Artigo inválido ou removido durante o build: ' . $slug);
            }
            $articles[$slug] = ['hash' => $hash, 'metadata' => $article->metadata->toArray()];
            $changed[$slug] = true;
        }
        $result = [
            'generated' => count($changed),
            'reused' => count($articles) - count($changed),
            'removed' => $removed,
            'unchanged' => !$full && $changed === [] && $removed === 0,
        ];
        if ($result['unchanged']) {
            return $result;
        }

        $metadata = array_column(array_values($articles), 'metadata');
        usort($metadata, static fn (array $a, array $b): int => [$b['date'], $b['slug']] <=> [$a['date'], $a['slug']]);
        $index = ArticleIndex::fromArticles($metadata);
        $application = new BlogApplication(new BlogController(
            $this->site,
            $index,
            $repository,
            new ArticleRenderer(new CommonMarkConverter([
                'html_input' => 'strip', 'allow_unsafe_links' => false,
            ]), $this->root . '/public'),
            new TemplateRenderer($this->root . '/templates', $this->site),
        ));

        $staging = $this->output . '.build-' . bin2hex(random_bytes(8));
        $temporaryManifest = $this->storage . '/build-manifest-' . bin2hex(random_bytes(8));
        try {
            BuildFiles::directory($staging);
            foreach ($articles as $slug => $article) {
                $file = '/artigos/' . $slug . '.html';
                if (isset($changed[$slug])) {
                    $this->render($application, '/artigos/' . $slug, $staging . $file);
                } else {
                    BuildFiles::copy($this->output . $file, $staging . $file);
                }
            }
            foreach ($this->aggregatePages($index) as $path => $file) {
                $this->render($application, $path, $staging . '/' . $file);
            }
            $this->copyPublicFiles($staging);

            // Never publish a mixture of source revisions if an editor changed files mid-build.
            if ($hashes !== $this->articleHashes() || $global !== $this->globalFingerprint()) {
                throw new RuntimeException('As fontes mudaram durante o build. Execute novamente.');
            }
            $manifest = [
                'version' => BuildManifest::VERSION,
                'global' => $global,
                'articles' => $articles,
                'outputs' => BuildFiles::inventory($staging),
            ];
            BuildFiles::write($temporaryManifest, json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ) . PHP_EOL);
            $publication->publish($staging, $temporaryManifest, $manifestPath);
        } finally {
            BuildFiles::remove($staging);
            BuildFiles::remove($temporaryManifest);
        }

        return $result;
    }

    /** @return array<string, string> */
    private function articleHashes(): array
    {
        if (!is_dir($this->articlesDirectory)) {
            throw new RuntimeException('Diretório de artigos não encontrado: ' . $this->articlesDirectory);
        }
        $hashes = [];
        foreach (new FilesystemIterator($this->articlesDirectory, FilesystemIterator::SKIP_DOTS) as $file) {
            if ($file->getExtension() !== 'md' || !$file->isFile()) {
                continue;
            }
            $slug = $file->getBasename('.md');
            if ($file->isLink() || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
                throw new RuntimeException('Arquivo de artigo inválido: ' . $file->getFilename());
            }
            $hashes[$slug] = BuildFiles::hash($file->getPathname());
        }
        ksort($hashes);

        return $hashes;
    }

    private function globalFingerprint(): string
    {
        $inputs = ['site' => $this->site, 'php' => PHP_VERSION, 'version' => BuildManifest::VERSION];
        foreach (['src', 'templates', 'config', 'bin', 'public/assets'] as $directory) {
            $inputs[$directory] = BuildFiles::inventory($this->root . '/' . $directory);
        }
        foreach (['composer.json', 'composer.lock', 'public/.htaccess'] as $file) {
            $inputs[$file] = BuildFiles::hash($this->root . '/' . $file);
        }

        return hash('sha256', json_encode($inputs, JSON_THROW_ON_ERROR));
    }

    /** @param array<string, mixed> $manifest */
    private function outputIsIntact(array $manifest): bool
    {
        try {
            return is_dir($this->output) && BuildFiles::inventory($this->output) === $manifest['outputs'];
        } catch (RuntimeException) {
            return false;
        }
    }

    /** @return array<string, string> */
    private function aggregatePages(ArticleIndex $index): array
    {
        $pages = [
            '/' => 'index.html', '/tags' => 'tags.html', '/404' => '404.html',
            '/sitemap.xml' => 'sitemap.xml', '/feed.xml' => 'feed.xml', '/robots.txt' => 'robots.txt',
        ];
        $perPage = $this->site['articles_per_page'];
        if (!is_int($perPage) || $perPage < 1) {
            throw new RuntimeException('articles_per_page precisa ser um inteiro positivo.');
        }
        for ($page = 2; $page <= (int) ceil(count($index->articles()) / $perPage); $page++) {
            $pages['/pagina/' . $page] = 'pagina/' . $page . '.html';
        }
        foreach ($index->tags() as $slug => $tag) {
            $path = '/tags/' . $slug;
            $pages[$path] = ltrim($path, '/') . '.html';
            for ($page = 2; $page <= (int) ceil($tag['count'] / $perPage); $page++) {
                $pages[$path . '/pagina/' . $page] = ltrim($path, '/') . '/pagina/' . $page . '.html';
            }
        }

        return $pages;
    }

    private function render(BlogApplication $application, string $path, string $destination): void
    {
        $response = $application->handle('GET', $path);
        $expected = $path === '/404' ? 404 : 200;
        if ($response->status !== $expected || $response->body === '') {
            throw new RuntimeException('Falha ao renderizar: ' . $path);
        }
        BuildFiles::write($destination, $response->body);
    }

    private function copyPublicFiles(string $staging): void
    {
        foreach (BuildFiles::inventory($this->root . '/public/assets') as $file => $hash) {
            if (
                preg_match('/\.(?:css|js|svg|png|jpe?g|webp|avif|gif|ico|woff2?|ttf|txt)$/i', $file) !== 1
                || preg_match('#(^|/)\.#', $file) === 1
            ) {
                throw new RuntimeException('Tipo de asset não permitido no site estático: ' . $file);
            }
            BuildFiles::copy($this->root . '/public/assets/' . $file, $staging . '/assets/' . $file);
        }
        BuildFiles::copy($this->root . '/public/.htaccess', $staging . '/.htaccess');
    }
}
