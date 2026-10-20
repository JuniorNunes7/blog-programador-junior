<?php if ($isHome): ?>
<section class="intro" aria-labelledby="intro-title">
    <div class="intro-copy">
        <p class="eyebrow"><span class="accent-line" aria-hidden="true"></span> Um caderno aberto</p>
        <h1 id="intro-title">Menos ego.<br>Mais <span class="underlined">aprendizado.</span></h1>
        <p class="intro-description"><?= $this->escape($site['description']) ?></p>
        <a class="text-link" href="#artigos">Explore os artigos <span aria-hidden="true">↓</span></a>
    </div>
    <div class="intro-art" aria-hidden="true">
        <picture>
            <source srcset="<?= $this->asset('/assets/images/editorial-paper-640.webp') ?> 640w, <?= $this->asset('/assets/images/editorial-paper-960.webp') ?> 960w" sizes="(min-width: 64rem) 380px, 42vw" type="image/webp">
            <img src="<?= $this->asset('/assets/images/editorial-paper.jpg') ?>" width="640" height="720" alt="" fetchpriority="high">
        </picture>
        <span class="art-note">código, ideias<br>e o que fica.</span>
        <span class="art-index">pj_ / caderno de aprendizados</span>
    </div>
</section>
<?php else: ?>
<header class="listing-heading">
    <a class="back-link" href="/">← Todos os artigos</a>
    <p class="eyebrow"><?= $activeTag === null ? 'O caderno continua' : 'Explorar por assunto' ?></p>
    <h1><?= $this->escape($heading) ?></h1>
    <p><?= $this->escape($description) ?></p>
</header>
<?php endif; ?>
<section id="artigos" class="articles-section" aria-label="Lista de artigos">
    <div class="section-heading"><h2 class="section-title"><?= $isHome ? 'Últimas anotações' : 'Artigos' ?></h2><span class="section-note">Do código à experiência.</span></div>
    <?php if ($availableTags !== []): ?>
    <nav class="topic-nav" aria-label="Filtrar por assunto">
        <a href="/" <?= $activeTag === null ? 'aria-current="page"' : '' ?>>Todos</a>
        <?php foreach ($availableTags as $slug => $tag): ?>
        <a href="<?= $this->escape($this->tagUrl($tag['name'])) ?>" <?= $activeTag === $slug ? 'aria-current="page"' : '' ?>><?= $this->escape($tag['name']) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if ($articles === []): ?>
    <div class="empty-state"><h2>O caderno ainda está em branco.</h2><p>Ainda não há artigos publicados. Volte em breve.</p></div>
    <?php else: ?>
    <div class="article-list">
        <?php foreach ($articles as $article): ?>
        <?= $this->render('components/article-card', ['article' => $article]) ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?= $this->render('components/pagination', compact('nextUrl', 'page', 'previousUrl', 'totalPages')) ?>
