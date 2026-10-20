<?php

declare(strict_types=1);

namespace Blog\Web;

use Blog\Content\Model\Article;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Renderer\Block\FencedCodeRenderer;
use League\CommonMark\Extension\CommonMark\Renderer\Block\IndentedCodeRenderer;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Table\TableRenderer;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

final readonly class ArticleRenderer
{
    public function __construct(private CommonMarkConverter $markdown, private string $publicDirectory)
    {
        $environment = $markdown->getEnvironment();
        $environment->addExtension(new TableExtension());
        $environment->addRenderer(FencedCode::class, new ScrollableBlockRenderer(
            new FencedCodeRenderer(),
            'Bloco de código',
        ), 10);
        $environment->addRenderer(IndentedCode::class, new ScrollableBlockRenderer(
            new IndentedCodeRenderer(),
            'Bloco de código',
        ), 10);
        $environment->addRenderer(Table::class, new ScrollableBlockRenderer(
            new TableRenderer(),
            'Tabela',
        ), 10);
    }

    public function render(Article $article): string
    {
        $environment = $this->markdown->getEnvironment();
        $document = (new MarkdownParser($environment))->parse($article->markdown);
        (new ArticleHeadings($article->metadata->title))->normalize($document);

        foreach ($document->iterator() as $node) {
            if ($node instanceof Image) {
                $this->prepareImage($node);
            }
        }

        return (new HtmlRenderer($environment))->renderDocument($document)->getContent();
    }

    private function prepareImage(Image $image): void
    {
        $image->data->set('attributes/loading', 'lazy');
        $image->data->set('attributes/decoding', 'async');
        $url = $image->getUrl();
        // Only inspect public local assets; never fetch remote Markdown image URLs.
        if (!str_starts_with($url, '/assets/')) {
            return;
        }

        $root = realpath($this->publicDirectory);
        $file = realpath($this->publicDirectory . rawurldecode(parse_url($url, PHP_URL_PATH) ?: ''));
        if ($root === false || $file === false || !str_starts_with($file, $root . '/assets/') || !is_file($file)) {
            return;
        }

        $dimensions = @getimagesize($file);
        if ($dimensions !== false) {
            $image->data->set('attributes/width', (string) $dimensions[0]);
            $image->data->set('attributes/height', (string) $dimensions[1]);
        }
    }
}
