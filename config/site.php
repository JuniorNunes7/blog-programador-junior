<?php

declare(strict_types=1);

return [
    'name' => 'Programador Junior',
    'description' => 'Artigos sobre programação, carreira e construção de software.',
    'author' => $_ENV['BLOG_AUTHOR'] ?? (getenv('BLOG_AUTHOR') ?: 'Junior Nunes'),
    'base_url' => $_ENV['BLOG_BASE_URL'] ?? (getenv('BLOG_BASE_URL') ?: 'https://example.com'),
    'articles_per_page' => 10,
];
