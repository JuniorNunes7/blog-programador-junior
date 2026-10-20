<header class="site-header">
    <div class="site-shell header-inner">
        <?= $this->render('components/brand') ?>
        <nav class="main-nav" aria-label="Principal">
            <a href="/">Artigos</a>
            <a href="/feed.xml" aria-label="Feed RSS">RSS<span aria-hidden="true"> ↗</span></a>
        </nav>
        <?= $this->render('components/theme-picker') ?>
    </div>
</header>
