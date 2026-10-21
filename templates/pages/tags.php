<header class="listing-heading topics-heading">
    <a class="back-link" href="/">← Todos os artigos</a>
    <p class="eyebrow">Explorar por assunto</p>
    <h1>Todos os assuntos</h1>
    <p><?= count($tags) ?> <?= count($tags) === 1 ? 'assunto publicado.' : 'assuntos publicados.' ?></p>
</header>

<?php if ($tags === []) : ?>
<div class="empty-state"><h2>O caderno ainda está em branco.</h2><p>Ainda não há assuntos publicados. Volte em breve.</p></div>
<?php else : ?>
<ul class="topic-catalog" aria-label="Todos os assuntos">
    <?php foreach ($tags as $tag) : ?>
    <li><a href="<?= $this->escape($this->tagUrl($tag['name'])) ?>"><span><?= $this->escape($tag['name']) ?></span><span class="topic-count"><?= $tag['count'] ?> <?= $tag['count'] === 1 ? 'artigo' : 'artigos' ?></span></a></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
