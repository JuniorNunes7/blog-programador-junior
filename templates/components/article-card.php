<article class="article-card">
    <?= $this->render('components/article-meta', ['date' => $article['date']]) ?>
    <div class="article-card-body">
        <?= $this->render('components/tag-links', ['tags' => $article['tags']]) ?>
        <h2><a href="<?= $this->escape($this->articleUrl($article['slug'])) ?>"><?= $this->escape($article['title']) ?><span class="article-arrow" aria-hidden="true"> ↗</span></a></h2>
        <p class="article-summary"><?= $this->escape($article['description']) ?></p>
    </div>
</article>
