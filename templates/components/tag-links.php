<?php if ($tags !== []) : ?>
    <p class="tags">
        <?php foreach ($tags as $tag) : ?>
            <a class="tag" href="<?= $this->escape($this->tagUrl($tag)) ?>"><?= $this->escape($tag) ?></a>
        <?php endforeach; ?>
    </p>
<?php endif; ?>
