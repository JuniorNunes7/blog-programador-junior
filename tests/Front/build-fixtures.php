<?php

declare(strict_types=1);

use Blog\Build\BuildFiles;
use Blog\Build\SiteBuilder;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$directory = $argv[1];
BuildFiles::directory($directory . '/articles');
for ($number = 1; $number <= 12; $number++) {
    $slug = 'artigo-' . $number;
    $title = $number === 1
        ? 'Um título longo para testar a leitura e o comportamento da interface em qualquer tamanho de tela'
        : 'Artigo de teste ' . $number;
    $description = 'Conteúdo de teste isolado para validar navegação, leitura e apresentação dos artigos.';
    $date = sprintf('2026-10-%02d', 20 - $number);
    $topics = ['reflexões', 'arquitetura', 'carreira', 'testes', 'docker', 'linux', 'laravel', 'aprendizados'];
    $tags = ['PHP', $topics[($number - 1) % count($topics)]];
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
        . "keywords:\n  - php\ntags:\n  - {$tags[0]}\n  - {$tags[1]}\n---\n\n$body",
    );
}

$site = require $root . '/config/site.php';
$site['base_url'] = 'http://127.0.0.1:8765';
$site['articles_per_page'] = 5;
$site['social'] = ['linkedin' => null, 'github' => null, 'instagram' => null];
(new SiteBuilder($root, $site, $directory . '/articles', $directory . '/dist', $directory . '/cache'))->build();

foreach (['empty', 'single', 'with-social'] as $variant) {
    $articles = $directory . '/' . $variant . '/articles';
    BuildFiles::directory($articles);
    $configuration = $site;
    if ($variant === 'single') {
        BuildFiles::copy($directory . '/articles/artigo-1.md', $articles . '/artigo-1.md');
    }
    if ($variant === 'with-social') {
        $articles = $directory . '/articles';
        $configuration['social'] = [
            'linkedin' => 'https://www.linkedin.com/in/programador-junior',
            'github' => 'https://github.com/programador-junior',
            'instagram' => 'https://www.instagram.com/programador-junior',
        ];
    }
    (new SiteBuilder(
        $root,
        $configuration,
        $articles,
        $directory . '/' . $variant . '/dist',
        $directory . '/' . $variant . '/cache',
    ))->build();
    BuildFiles::copy(
        $directory . '/' . $variant . '/dist/index.html',
        $directory . '/dist/__' . $variant . '.html',
    );
}
