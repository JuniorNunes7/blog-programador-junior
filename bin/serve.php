<?php

declare(strict_types=1);

// Development only: production serves dist/ directly with Apache, without PHP.
require dirname(__DIR__) . '/vendor/autoload.php';

(new Blog\Web\StaticFileServer(dirname(__DIR__) . '/dist'))->serve(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
);
