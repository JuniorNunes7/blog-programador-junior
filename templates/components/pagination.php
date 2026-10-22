<?php if ($totalPages > 1) : ?>
<nav class="pagination" aria-label="Paginação">
    <?php if ($previousUrl !== null) :
        ?><a class="button button-secondary" rel="prev" href="<?= $this->escape($previousUrl) ?>"><span aria-hidden="true">← </span>Mais recentes</a><?php
    else :
        ?><span></span><?php
    endif; ?>
    <span class="page-number" aria-current="page">Página <?= $page ?> de <?= $totalPages ?></span>
    <?php if ($nextUrl !== null) :
        ?><a class="button button-secondary" rel="next" href="<?= $this->escape($nextUrl) ?>">Mais antigos<span aria-hidden="true"> →</span></a><?php
    else :
        ?><span></span><?php
    endif; ?>
</nav>
<?php endif; ?>
