<?php

declare(strict_types=1);

return [
    'name' => 'Programador Junior',
    'description' => 'Experiências reais, erros e aprendizados sobre desenvolvimento 
        de software e carreira. Menos ego, mais aprendizado.',
    'author' => getenv('BLOG_AUTHOR') ?: 'Junior Nunes',
    'base_url' => getenv('BLOG_BASE_URL') ?: 'https://example.com',
    'articles_per_page' => 10,
    'social' => [
        'linkedin' =>  getenv('BLOG_LINKEDIN_URL') ?: null,
        'github' =>  getenv('BLOG_GITHUB_URL') ?: null,
        'instagram' =>  getenv('BLOG_INSTAGRAM_URL') ?: null,
    ],
];
