<!doctype html>
<html lang="pt-BR">
<head>
    <?= $this->render('components/head', compact('title', 'site', 'description', 'article', 'canonicalUrl', 'schemaJson', 'noindex', 'pageStyle')) ?>
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <?= $this->render('components/header') ?>
    <main id="conteudo" class="site-shell" tabindex="-1"><?= $content ?></main>
    <?= $this->render('components/footer', ['site' => $site]) ?>
</body>
</html>
