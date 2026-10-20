<article class="post">
    <a class="back-link" href="/">← Todos os artigos</a>
    <header class="post-header">
        <?= $this->render('components/tag-links', ['tags' => $article->metadata->tags]) ?>
        <h1><?= $this->escape($article->metadata->title) ?></h1>
        <p class="post-description"><?= $this->escape($article->metadata->description) ?></p>
        <?= $this->render('components/article-meta', ['date' => $article->metadata->publishedAt->format('Y-m-d'), 'author' => $site['author']]) ?>
    </header>
    <div class="prose"><?= $articleHtml ?></div>
    <footer class="post-footer"><span class="accent-line" aria-hidden="true"></span><p>Menos ego. Mais aprendizado.</p><a class="text-link" href="/">Continue explorando os artigos <span aria-hidden="true">→</span></a></footer>
</article>
