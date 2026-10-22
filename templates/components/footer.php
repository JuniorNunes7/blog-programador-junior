<footer class="site-footer">
    <div class="site-shell footer-inner">
        <div><p class="footer-signature">Menos ego. Mais aprendizado.</p><p>Escrito por <?= $this->escape($site['author']) ?>.</p></div>
        <?php $social = $site['social'] ?? []; ?>
        <?php if (array_filter($social)) : ?>
        <nav class="footer-social" aria-label="Redes de <?= $this->escape($site['author']) ?>">
            <?= $this->render('components/social/linkedin', ['author' => $site['author'], 'url' => $social['linkedin'] ?? null]) ?>
            <?= $this->render('components/social/github', ['author' => $site['author'], 'url' => $social['github'] ?? null]) ?>
            <?= $this->render('components/social/instagram', ['author' => $site['author'], 'url' => $social['instagram'] ?? null]) ?>
        </nav>
        <?php endif; ?>
    </div>
</footer>
