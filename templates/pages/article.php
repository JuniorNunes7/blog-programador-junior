<article class="post">
    <a class="back-link" href="/">← Todos os artigos</a>
    <header>
        <p class="eyebrow"><?= $this->escape($article->metadata->publishedAt->format('d/m/Y')) ?></p>
        <h1><?= $this->escape($article->metadata->title) ?></h1>
        <p class="post-description"><?= $this->escape($article->metadata->description) ?></p>
        <?= $this->render('components/tag-links', ['tags' => $article->metadata->tags]) ?>
    </header>
    <div class="prose"><?= $articleHtml ?></div>
</article>
