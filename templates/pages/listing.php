<?php if (!$isHome) : ?>
<header class="listing-heading">
    <a class="back-link" href="/">← Todos os artigos</a>
    <p class="eyebrow">Explorar por assunto</p>
    <h1><?= $this->escape($heading) ?></h1>
    <p><?= $this->escape($description) ?></p>
</header>
<?php endif; ?>
<section id="artigos" class="articles-section<?= $isHome ? ' articles-section--home' : '' ?>" aria-labelledby="articles-title">
    <div class="section-heading">
        <div>
            <p class="eyebrow"><span class="accent-line" aria-hidden="true"></span>Índice</p>
            <?php if ($isHome) : ?>
            <h1 id="articles-title" class="section-title">Artigos recentes</h1>
            <?php else : ?>
            <h2 id="articles-title" class="section-title">Artigos</h2>
            <?php endif; ?>
        </div>
        <p class="article-count"><?= $articleCount ?> <?= $articleCount === 1 ? 'publicação' : 'publicações' ?></p>
    </div>
<?php if ($featuredTags !== []) : ?>
    <nav class="topic-nav" aria-label="Filtrar por assunto">
        <a href="/" <?= $activeTag === null ? 'aria-current="page"' : '' ?>>Todos os artigos</a>
        <?php foreach ($featuredTags as $slug => $tag) : ?>
        <a href="<?= $this->escape($this->tagUrl($tag['name'])) ?>" <?= $activeTag === $slug ? 'aria-current="page"' : '' ?>><?= $this->escape($tag['name']) ?></a>
        <?php endforeach; ?>
        <a class="topic-catalog-link" href="/tags">Todos os assuntos <span aria-hidden="true">(<?= $tagCount ?>)</span></a>
    </nav>
<?php endif; ?>
    <?php if ($articles === []) : ?>
    <div class="empty-state"><h2>O caderno ainda está em branco.</h2><p>Ainda não há artigos publicados. Volte em breve.</p></div>
    <?php else : ?>
    <div class="article-list<?= count($articles) === 1 ? ' article-list--single' : '' ?>">
        <?php foreach ($articles as $article) : ?>
            <?= $this->render('components/article-card', ['article' => $article]) ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?= $this->render('components/pagination', compact('nextUrl', 'page', 'previousUrl', 'totalPages')) ?>
