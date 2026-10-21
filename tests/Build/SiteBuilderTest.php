<?php

declare(strict_types=1);

namespace Tests\Build;

use Blog\Build\BuildFiles;
use Blog\Build\SiteBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Blog\Content\Errors\ContentException;

final class SiteBuilderTest extends TestCase
{
    private string $root;
    private array $site;

    protected function setUp(): void
    {
        $source = dirname(__DIR__, 2);
        $this->root = sys_get_temp_dir() . '/pj-build-test-' . bin2hex(random_bytes(8));
        foreach (['src', 'templates', 'config', 'bin', 'public/assets'] as $directory) {
            foreach (BuildFiles::inventory($source . '/' . $directory) as $file => $hash) {
                BuildFiles::copy(
                    $source . '/' . $directory . '/' . $file,
                    $this->root . '/' . $directory . '/' . $file,
                );
            }
        }
        foreach (['composer.json', 'composer.lock', 'public/.htaccess'] as $file) {
            BuildFiles::copy($source . '/' . $file, $this->root . '/' . $file);
        }
        BuildFiles::directory($this->root . '/content/articles');
        $this->site = require $source . '/config/site.php';
        $this->site['base_url'] = 'https://example.test';
        $this->site['articles_per_page'] = 1;
        $this->article('primeiro');
        $this->article('segundo');
    }

    protected function tearDown(): void
    {
        BuildFiles::remove($this->root);
    }

    public function testItBuildsAStandaloneSiteWithoutAnIndexPrerequisite(): void
    {
        $result = $this->build();
        self::assertSame(2, $result['generated']);
        foreach (
            [
            'index.html', 'artigos/primeiro.html', 'artigos/segundo.html', 'pagina/2.html',
            'tags.html', 'tags/php.html', 'tags/php/pagina/2.html', '404.html',
            'sitemap.xml', 'feed.xml', 'robots.txt', '.htaccess', 'assets/css/base.css',
            ] as $file
        ) {
            self::assertFileExists($this->root . '/dist/' . $file);
        }
        self::assertFileDoesNotExist($this->root . '/storage/article-index.json');
        foreach (array_keys(BuildFiles::inventory($this->root . '/dist')) as $file) {
            self::assertDoesNotMatchRegularExpression('/\.(?:php|md|json)$/', $file);
        }
        self::assertStringContainsString(
            'https://example.test/artigos/primeiro',
            $this->generatedFile('artigos/primeiro.html'),
        );
        self::assertStringContainsString('"@type":"BlogPosting"', $this->generatedFile('artigos/primeiro.html'));
        self::assertStringContainsString('noindex, follow', $this->generatedFile('404.html'));
    }

    public function testItDoesNotRewriteAnythingWhenInputsAreUnchanged(): void
    {
        $this->build();
        touch($this->root . '/dist/index.html', 100);
        touch($this->root . '/storage/build-manifest.json', 100);
        $before = BuildFiles::inventory($this->root . '/dist');
        self::assertSame(['generated' => 0, 'reused' => 2, 'removed' => 0, 'unchanged' => true], $this->build());
        clearstatcache();
        self::assertSame(100, filemtime($this->root . '/dist/index.html'));
        self::assertSame(100, filemtime($this->root . '/storage/build-manifest.json'));
        self::assertSame($before, BuildFiles::inventory($this->root . '/dist'));
    }

    public function testBodyChangesRenderOnlyThatArticleAndPreserveReusedFiles(): void
    {
        $this->build();
        touch($this->root . '/dist/artigos/segundo.html', 100);
        $old = $this->generatedFile('artigos/segundo.html');
        $this->article('primeiro', body: 'Novo corpo exclusivo.');
        self::assertSame(['generated' => 1, 'reused' => 1, 'removed' => 0, 'unchanged' => false], $this->build());
        self::assertStringContainsString('Novo corpo exclusivo.', $this->generatedFile('artigos/primeiro.html'));
        self::assertSame($old, $this->generatedFile('artigos/segundo.html'));
        clearstatcache();
        self::assertSame(100, filemtime($this->root . '/dist/artigos/segundo.html'));
    }

    public function testMetadataChangesRefreshListingsTagsFeedAndSitemap(): void
    {
        $this->build();
        $this->article('primeiro', tag: 'Arquitetura', title: 'Título atualizado', date: '2026-10-21');
        $this->build();
        foreach (['index.html', 'feed.xml', 'artigos/primeiro.html'] as $file) {
            self::assertStringContainsString('Título atualizado', $this->generatedFile($file));
        }
        self::assertStringContainsString('/tags/arquitetura', $this->generatedFile('sitemap.xml'));
        self::assertFileExists($this->root . '/dist/tags/arquitetura.html');
        self::assertFileDoesNotExist($this->root . '/dist/tags/php/pagina/2.html');
    }

    public function testAdditionsRenamesAndDeletionsRemoveObsoletePages(): void
    {
        $this->build();
        $this->article('terceiro', tag: 'Outro');
        self::assertSame(1, $this->build()['generated']);
        rename($this->root . '/content/articles/terceiro.md', $this->root . '/content/articles/123.md');
        $result = $this->build();
        self::assertSame(1, $result['generated']);
        self::assertSame(1, $result['removed']);
        self::assertFileDoesNotExist($this->root . '/dist/artigos/terceiro.html');
        self::assertFileExists($this->root . '/dist/artigos/123.html');
        self::assertTrue($this->build()['unchanged']);
        unlink($this->root . '/content/articles/123.md');
        unlink($this->root . '/content/articles/segundo.md');
        self::assertSame(2, $this->build()['removed']);
        self::assertFileDoesNotExist($this->root . '/dist/tags/outro.html');
        self::assertFileDoesNotExist($this->root . '/dist/pagina/2.html');
        self::assertFileDoesNotExist($this->root . '/dist/pagina/3.html');
        self::assertStringNotContainsString('/artigos/123', $this->generatedFile('sitemap.xml'));
    }

    public function testAnEmptyBlogStillBuildsAndDocumentationIsIgnored(): void
    {
        unlink($this->root . '/content/articles/primeiro.md');
        unlink($this->root . '/content/articles/segundo.md');
        BuildFiles::write($this->root . '/README.md', 'Documentação sem front matter.');
        self::assertSame(0, $this->build()['generated']);
        self::assertStringContainsString('Ainda não há artigos', $this->generatedFile('index.html'));
        self::assertFileExists($this->root . '/dist/tags.html');
        self::assertFileDoesNotExist($this->root . '/dist/pagina/2.html');
        self::assertTrue($this->build()['unchanged']);
    }

    #[DataProvider('globalFiles')]
    public function testGlobalChangesRebuildEveryArticle(string $file): void
    {
        $this->build();
        file_put_contents($this->root . '/' . $file, "\n", FILE_APPEND);
        self::assertSame(2, $this->build()['generated']);
    }

    public static function globalFiles(): array
    {
        return array_map(static fn (string $file): array => [$file], [
            'templates/layout.php', 'public/assets/css/base.css', 'config/site.php',
            'src/Web/ArticleRenderer.php', 'composer.lock', 'public/.htaccess',
        ]);
    }

    public function testEffectiveConfigurationInvalidatesHtmlWithoutLeakingItsValuesToTheManifest(): void
    {
        $this->build();
        $this->site['author'] = 'Autor da configuração efetiva';
        $this->site['base_url'] = 'https://novo.example.test';
        self::assertSame(2, $this->build()['generated']);
        self::assertStringContainsString($this->site['author'], $this->generatedFile('index.html'));
        self::assertStringContainsString($this->site['base_url'], $this->generatedFile('sitemap.xml'));
        $manifest = file_get_contents($this->root . '/storage/build-manifest.json');
        self::assertStringNotContainsString($this->site['author'], $manifest);
        self::assertStringNotContainsString($this->site['base_url'], $manifest);
    }

    #[DataProvider('damagedBuilds')]
    public function testMissingOrDamagedCacheAndOutputTriggerAFullRebuild(string $damage): void
    {
        $this->build();
        match ($damage) {
            'missing-cache' => unlink($this->root . '/storage/build-manifest.json'),
            'invalid-cache' => file_put_contents($this->root . '/storage/build-manifest.json', '{invalid'),
            'invalid-schema' => file_put_contents($this->root . '/storage/build-manifest.json', '{"version":1}'),
            'missing-output' => unlink($this->root . '/dist/artigos/primeiro.html'),
            'changed-output' => file_put_contents($this->root . '/dist/artigos/primeiro.html', 'corrompido'),
            'extra-output' => file_put_contents($this->root . '/dist/rogue.php', '<?php'),
        };
        self::assertSame(2, $this->build()['generated']);
        self::assertFileDoesNotExist($this->root . '/dist/rogue.php');
        self::assertStringContainsString('BlogPosting', $this->generatedFile('artigos/primeiro.html'));
    }

    public static function damagedBuilds(): array
    {
        return array_map(static fn (string $case): array => [$case], [
            'missing-cache', 'invalid-cache', 'invalid-schema', 'missing-output', 'changed-output', 'extra-output',
        ]);
    }

    public function testFullBuildOverridesAnOtherwiseValidCache(): void
    {
        $this->build();
        self::assertSame(2, (new SiteBuilder($this->root, $this->site))->build(true)['generated']);
    }

    public function testInvalidContentPreservesThePublishedSiteAndManifest(): void
    {
        $this->build();
        $before = BuildFiles::inventory($this->root . '/dist');
        $manifest = file_get_contents($this->root . '/storage/build-manifest.json');
        BuildFiles::write($this->root . '/content/articles/primeiro.md', 'Sem front matter');
        try {
            $this->build();
            self::fail('O conteúdo inválido deveria impedir a publicação.');
        } catch (ContentException $exception) {
            self::assertNotEmpty($exception->getMessage());
        }
        self::assertSame($before, BuildFiles::inventory($this->root . '/dist'));
        self::assertSame($manifest, file_get_contents($this->root . '/storage/build-manifest.json'));
    }

    public function testUnsafeAssetsCannotBePublishedAndPreserveThePreviousBuild(): void
    {
        $this->build();
        $before = BuildFiles::inventory($this->root . '/dist');
        BuildFiles::write($this->root . '/public/assets/source.php', '<?php');
        try {
            $this->build();
            self::fail('Um asset PHP não pode fazer parte do artefato.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Tipo de asset', $exception->getMessage());
        }
        self::assertSame($before, BuildFiles::inventory($this->root . '/dist'));
        self::assertSame([], glob($this->root . '/dist.build-*'));
    }

    public function testAnotherBuildCannotAcquireTheLock(): void
    {
        BuildFiles::directory($this->root . '/storage');
        $lock = fopen($this->root . '/storage/build.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $this->expectExceptionMessage('Já existe um build em execução');
            $this->build();
        } finally {
            fclose($lock);
        }
    }

    private function build(): array
    {
        return (new SiteBuilder($this->root, $this->site))->build();
    }

    private function generatedFile(string $file): string
    {
        return file_get_contents($this->root . '/dist/' . $file);
    }

    private function article(
        string $slug,
        string $body = 'Texto do artigo.',
        string $tag = 'PHP',
        string $title = 'Título do artigo',
        string $date = '2026-10-18',
    ): void {
        BuildFiles::write(
            $this->root . '/content/articles/' . $slug . '.md',
            "---\ntitle: $title\ndate: $date\ndescription: Descrição do artigo\n"
            . "keywords:\n  - php\ntags:\n  - $tag\n---\n\n$body\n"
        );
    }
}
