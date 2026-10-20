<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->escape($title . ' · ' . $site['name']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <header class="site-header"><div class="site-shell"><a class="brand" href="/"><?= $this->escape($site['name']) ?></a><nav aria-label="Principal"><a href="/">Artigos</a></nav></div></header>
    <main id="conteudo" class="site-shell"><?= $content ?></main>
    <footer class="site-footer"><div class="site-shell">Escrito por <?= $this->escape($site['author']) ?>.</div></footer>
</body>
</html>
