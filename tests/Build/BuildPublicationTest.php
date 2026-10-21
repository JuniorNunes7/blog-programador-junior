<?php

declare(strict_types=1);

namespace Tests\Build;

use Blog\Build\BuildFiles;
use Blog\Build\BuildPublication;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BuildPublicationTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/pj-publication-' . bin2hex(random_bytes(8));
        BuildFiles::write($this->directory . '/dist/index.html', 'anterior');
        BuildFiles::write($this->directory . '/staging/index.html', 'novo');
        BuildFiles::write($this->directory . '/manifest.tmp', '{}');
    }

    protected function tearDown(): void
    {
        BuildFiles::remove($this->directory);
    }

    public function testManifestWriteFailureRestoresThePreviousSite(): void
    {
        // A directory at the manifest destination causes rename to fail even when running as root.
        BuildFiles::directory($this->directory . '/manifest.json');
        try {
            (new BuildPublication($this->directory . '/dist'))->publish(
                $this->directory . '/staging',
                $this->directory . '/manifest.tmp',
                $this->directory . '/manifest.json',
            );
            self::fail('A gravação do manifesto deveria falhar.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('manifesto', $exception->getMessage());
        }
        self::assertSame('anterior', file_get_contents($this->directory . '/dist/index.html'));
    }

    public function testFailedDirectorySwapRestoresThePreviousSite(): void
    {
        try {
            (new BuildPublication($this->directory . '/dist'))->publish(
                $this->directory . '/missing',
                $this->directory . '/manifest.tmp',
                $this->directory . '/manifest.json',
            );
            self::fail('A troca de diretórios deveria falhar.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('publicar o site', $exception->getMessage());
        }
        self::assertSame('anterior', file_get_contents($this->directory . '/dist/index.html'));
    }

    public function testInterruptedSwapCanBeRecoveredOnTheNextBuild(): void
    {
        rename($this->directory . '/dist', $this->directory . '/dist.previous');
        (new BuildPublication($this->directory . '/dist'))->recover();
        self::assertSame('anterior', file_get_contents($this->directory . '/dist/index.html'));
    }

    public function testWriteFailureIsReported(): void
    {
        BuildFiles::write($this->directory . '/blocked', 'arquivo');
        $this->expectException(RuntimeException::class);
        BuildFiles::write($this->directory . '/blocked/child.html', 'conteúdo');
    }
}
