<?php if ($tags !== []) : ?>
<ul class="tags" aria-label="Tags do artigo">
    <?php foreach ($tags as $tag) : ?>
    <li><a class="tag" href="<?= $this->escape($this->tagUrl($tag)) ?>"><span aria-hidden="true">#</span><?= $this->escape($tag) ?></a></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
