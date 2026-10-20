<article class="article-card">
    <p class="eyebrow"><?= $this->escape($article['date']) ?></p>
    <h2><a href="<?= $this->escape($this->articleUrl($article['slug'])) ?>"><?= $this->escape($article['title']) ?></a></h2>
    <p><?= $this->escape($article['description']) ?></p>
    <?= $this->render('components/tag-links', ['tags' => $article['tags']]) ?>
</article>
