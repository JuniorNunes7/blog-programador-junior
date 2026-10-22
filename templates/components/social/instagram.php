<?php if (is_string($url) && $url !== '') : ?>
<a class="social-link" href="<?= $this->escape($url) ?>" target="_blank" rel="me noopener noreferrer" aria-label="Instagram de <?= $this->escape($author) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1"></circle></svg>
</a>
<?php endif; ?>
