#!/usr/bin/env php
<?php

declare(strict_types=1);

use Blog\Build\SiteBuilder;
use Blog\Web\Environment;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

try {
    if (array_diff(array_slice($argv, 1), ['--full']) !== []) {
        throw new InvalidArgumentException('Uso: php bin/build.php [--full]');
    }
    Environment::load($root . '/.env');
    $site = require $root . '/config/site.php';
    $result = (new SiteBuilder($root, $site))->build(in_array('--full', $argv, true));
    printf(
        "%s %d artigo(s) gerado(s), %d reaproveitado(s), %d removido(s).%s",
        $result['unchanged'] ? 'Site sem alterações.' : 'Site gerado em dist/.',
        $result['generated'],
        $result['reused'],
        $result['removed'],
        PHP_EOL,
    );
} catch (Throwable $exception) {
    fwrite(STDERR, 'Erro ao gerar o site: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
