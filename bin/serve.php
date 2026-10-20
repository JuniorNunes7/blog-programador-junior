<?php

declare(strict_types=1);

// Router for PHP's local server, including sitemap, feed and unknown dotted URLs.
$public = dirname(__DIR__) . '/public';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath($public . rawurldecode($path));

if (
    str_starts_with($path, '/assets/')
    && $file !== false
    && str_starts_with($file, $public . '/assets/')
    && is_file($file)
) {
    return false;
}

require $public . '/index.php';
