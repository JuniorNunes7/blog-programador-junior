<?php if (is_string($url) && $url !== ''): ?>
<a class="social-link" href="<?= $this->escape($url) ?>" target="_blank" rel="me noopener noreferrer" aria-label="LinkedIn de <?= $this->escape($author) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="currentColor"><path d="M6.5 8.4H3.2V21h3.3ZM4.9 3A1.9 1.9 0 1 0 5 6.8 1.9 1.9 0 0 0 4.9 3ZM21 13.7c0-3.8-2-5.6-4.7-5.6-2.2 0-3.2 1.2-3.7 2.1v-1.8H9.3V21h3.3v-6.2c0-1.6.3-3.2 2.3-3.2 2 0 2 1.9 2 3.3V21H21Z"></path></svg>
</a>
<?php endif; ?>
