<?php

declare(strict_types=1);

use Blog\Content\Infrastructure\FrontMatterParser;
use Blog\Content\Infrastructure\MarkdownArticleRepository;
use Blog\Web\ArticleIndex;
use Blog\Web\ArticleRenderer;
use Blog\Web\BlogApplication;
use Blog\Web\BlogController;
use Blog\Web\TemplateRenderer;
use League\CommonMark\CommonMarkConverter;

$root = dirname(__DIR__, 2);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/assets/')) {
    return false;
}

require $root . '/vendor/autoload.php';
// Each fixture server owns its data; no production content or index is changed.
$directory = sys_get_temp_dir() . '/pj-front-' . getmypid();
if (!is_dir($directory)) {
    mkdir($directory . '/articles', 0775, true);
    $articles = [];
    for ($number = 1; $number <= 12; $number++) {
        $slug = 'artigo-' . $number;
        $title = $number === 1
            ? 'Um título longo para testar a leitura e o comportamento da interface em qualquer tamanho de tela'
            : 'Artigo de teste ' . $number;
        $description = 'Conteúdo de teste isolado para validar navegação, leitura e apresentação dos artigos.';
        $date = sprintf('2026-10-%02d', 20 - $number);
        $tags = ['PHP', 'reflexões'];
        $articles[] = compact('slug', 'title', 'date', 'description', 'tags') + ['keywords' => ['php']];
        $body = "# $title\n\n";
        if ($number === 1) {
            $body .= "## Leitura e hierarquia\n\n";
            $body .= str_repeat(
                "Este parágrafo pertence somente à fixture de teste. Ele permite avaliar a largura da coluna, "
                . "o espaçamento e o ritmo de leitura com conteúdo suficiente.\n\n",
                6,
            );
            $body .= "### Código e referências\n\n[Um link acessível](/tags/php).\n\n";
            $body .= "```php\n" . '$texto = "' . str_repeat('linha-longa-', 30) . '";' . "\n```\n\n";
            $body .= "> Uma citação para verificar o contraste.\n\n- Primeiro item\n- Segundo item\n\n";
            $body .= "| Campo | Valor |\n| --- | --- |\n| Exemplo | " . str_repeat('dado-longo-', 30) . " |\n\n";
            $body .= "![Composição abstrata de papel](/assets/images/editorial-paper-640.webp)\n\n";
            $body .= "# Outra seção\n\n<script>alert('unsafe')</script>\n\n";
        }
        file_put_contents(
            $directory . '/articles/' . $slug . '.md',
            "---\ntitle: $title\ndate: $date\ndescription: $description\n"
            . "keywords:\n  - php\ntags:\n  - PHP\n  - reflexões\n---\n\n$body",
        );
    }
    file_put_contents($directory . '/index.json', json_encode(['articles' => $articles], JSON_THROW_ON_ERROR));
    file_put_contents($directory . '/empty.json', '{"articles":[]}');
}
$empty = $path === '/__empty';
$site = require $root . '/config/site.php';
$site['base_url'] = 'http://127.0.0.1:8765';
$site['articles_per_page'] = 5;
$application = new BlogApplication(new BlogController(
    $site,
    ArticleIndex::fromFile($directory . ($empty ? '/empty.json' : '/index.json')),
    new MarkdownArticleRepository($directory . '/articles', new FrontMatterParser()),
    new ArticleRenderer(
        new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]),
        $root . '/public',
    ),
    new TemplateRenderer($root . '/templates', $site),
));
$application->handle($_SERVER['REQUEST_METHOD'], $empty ? '/' : $_SERVER['REQUEST_URI'])->send();
