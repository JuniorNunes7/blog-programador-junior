<?php

declare(strict_types=1);

use Blog\Content\Errors\ContentException;
use Blog\Content\Infrastructure\FrontMatterParser;
use Blog\Content\Infrastructure\MarkdownArticleRepository;
use Blog\Web\ArticleIndex;
use Blog\Web\ArticleRenderer;
use Blog\Web\BlogApplication;
use Blog\Web\BlogController;
use Blog\Web\TemplateRenderer;
use League\CommonMark\CommonMarkConverter;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$site = require $root . '/config/site.php';

try {
    $application = new BlogApplication(
        new BlogController(
            $site,
            ArticleIndex::fromFile($root . '/storage/article-index.json'),
            new MarkdownArticleRepository($root . '/content/articles', new FrontMatterParser()),
            new ArticleRenderer(new CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]), $root . '/public'),
            new TemplateRenderer($root . '/templates', $site),
        ),
    );

    $application->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/')->send();
} catch (ContentException $exception) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'O blog está temporariamente indisponível. Gere o índice de artigos antes de publicar.';
}
