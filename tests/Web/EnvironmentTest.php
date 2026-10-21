<?php

declare(strict_types=1);

namespace Tests\Web;

use Blog\Web\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'blog-env-');
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    public function testItLoadsQuotedValuesAndSkipsComments(): void
    {
        file_put_contents($this->path, "# Comentário\nPJ_ENV_AUTHOR=\"Junior Nunes\"\nPJ_ENV_EMPTY=\n");

        Environment::load($this->path);

        self::assertSame('Junior Nunes', getenv('PJ_ENV_AUTHOR'));
        self::assertSame('', getenv('PJ_ENV_EMPTY'));
        $this->clear('PJ_ENV_AUTHOR');
        $this->clear('PJ_ENV_EMPTY');
    }

    public function testItDoesNotOverrideAProcessEnvironmentVariable(): void
    {
        putenv('PJ_ENV_PRIORITY=servidor');
        $_ENV['PJ_ENV_PRIORITY'] = 'servidor';
        file_put_contents($this->path, 'PJ_ENV_PRIORITY=arquivo');

        Environment::load($this->path);

        self::assertSame('servidor', getenv('PJ_ENV_PRIORITY'));
        $this->clear('PJ_ENV_PRIORITY');
    }

    private function clear(string $key): void
    {
        putenv($key);
        unset($_ENV[$key]);
    }
}
