<section class="intro">
    <p class="eyebrow">Conhecimento compartilhado</p>
    <h1><?= $this->escape($heading) ?></h1>
    <p><?= $this->escape($description) ?></p>
</section>

<?php if ($articles === []) : ?>
    <p class="empty-state">Ainda não há artigos publicados.</p>
<?php else : ?>
    <section class="article-list" aria-label="Lista de artigos">
        <?php foreach ($articles as $article) : ?>
            <?= $this->render('components/article-card', ['article' => $article]) ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?= $this->render('components/pagination', compact('nextUrl', 'page', 'previousUrl', 'totalPages')) ?>
