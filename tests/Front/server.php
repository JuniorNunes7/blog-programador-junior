<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

(new Blog\Web\StaticFileServer(getenv('PJ_FRONT_DIRECTORY') . '/dist'))->serve(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
);
