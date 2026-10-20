<?php if ($totalPages > 1) : ?>
    <nav class="pagination" aria-label="Paginação">
        <?php if ($previousUrl === null) : ?>
            <span></span>
        <?php else : ?>
            <a href="<?= $this->escape($previousUrl) ?>">← Mais recentes</a>
        <?php endif; ?>
        <span>Página <?= $page ?> de <?= $totalPages ?></span>
        <?php if ($nextUrl === null) : ?>
            <span></span>
        <?php else : ?>
            <a href="<?= $this->escape($nextUrl) ?>">Mais antigos →</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
