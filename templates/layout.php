<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->escape($title . ' · ' . $site['name']) ?></title>
    <meta name="description" content="<?= $this->escape($description) ?>">
    <?php if ($article !== null): ?>
        <meta name="keywords" content="<?= $this->escape(implode(', ', $article->metadata->keywords)) ?>">
        <meta property="article:published_time" content="<?= $this->escape($article->metadata->publishedAt->format(DATE_ATOM)) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= $this->escape($canonicalUrl) ?>">
    <meta property="og:type" content="<?= $article === null ? 'website' : 'article' ?>">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="<?= $this->escape($site['name']) ?>">
    <meta property="og:title" content="<?= $this->escape($title . ' · ' . $site['name']) ?>">
    <meta property="og:description" content="<?= $this->escape($description) ?>">
    <meta property="og:url" content="<?= $this->escape($canonicalUrl) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= $this->escape($title . ' · ' . $site['name']) ?>">
    <meta name="twitter:description" content="<?= $this->escape($description) ?>">
    <script type="application/ld+json"><?= htmlspecialchars($schemaJson, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></script>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
    <header class="site-header"><div class="site-shell"><a class="brand" href="/"><?= $this->escape($site['name']) ?></a><nav aria-label="Principal"><a href="/">Artigos</a></nav></div></header>
    <main id="conteudo" class="site-shell"><?= $content ?></main>
    <footer class="site-footer"><div class="site-shell">Escrito por <?= $this->escape($site['author']) ?>.</div></footer>
</body>
</html>
