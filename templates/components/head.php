<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= $this->escape($title . ' · ' . $site['name']) ?></title>
<meta name="description" content="<?= $this->escape($description) ?>">
<?php if ($noindex) : ?>
<meta name="robots" content="noindex, follow">
<?php else : ?>
<link rel="canonical" href="<?= $this->escape($canonicalUrl) ?>">
<meta property="og:url" content="<?= $this->escape($canonicalUrl) ?>">
<?php endif; ?>
<?php if ($article !== null) : ?>
<meta name="keywords" content="<?= $this->escape(implode(', ', $article->metadata->keywords)) ?>">
<meta property="article:published_time" content="<?= $this->escape($article->metadata->publishedAt->format(DATE_ATOM)) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= $article === null ? 'website' : 'article' ?>">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="<?= $this->escape($site['name']) ?>">
<meta property="og:title" content="<?= $this->escape($title . ' · ' . $site['name']) ?>">
<meta property="og:description" content="<?= $this->escape($description) ?>">
<meta property="og:image" content="<?= $this->escape($this->absoluteUrl($this->asset('/assets/brand/banner-light.png'))) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Programador Junior. Menos ego. Mais aprendizado.">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $this->escape($title . ' · ' . $site['name']) ?>">
<meta name="twitter:description" content="<?= $this->escape($description) ?>">
<meta name="twitter:image" content="<?= $this->escape($this->absoluteUrl($this->asset('/assets/brand/banner-light.png'))) ?>">
<meta name="twitter:image:alt" content="Programador Junior. Menos ego. Mais aprendizado.">
<?php if (!$noindex) : ?>
<script type="application/ld+json"><?= $schemaJson ?></script>
<?php endif; ?>
<link rel="alternate" type="application/rss+xml" title="Programador Junior — RSS" href="/feed.xml">
<link rel="icon" type="image/svg+xml" href="<?= $this->asset('/assets/brand/favicon.svg') ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $this->asset('/assets/brand/favicon-32.png') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $this->asset('/assets/brand/favicon-16.png') ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $this->asset('/assets/brand/apple-touch-icon.png') ?>">
<script src="<?= $this->asset('/assets/js/theme-init.js') ?>"></script>
<link rel="preload" href="/assets/fonts/source-sans-3-400.woff2" as="font" type="font/woff2" crossorigin>
<?php foreach (['tokens', 'base', 'components', 'pages/' . $pageStyle] as $stylesheet) : ?>
<link rel="stylesheet" href="<?= $this->asset('/assets/css/' . $stylesheet . '.css') ?>">
<?php endforeach; ?>
<script type="module" src="<?= $this->asset('/assets/js/theme.js') ?>"></script>
