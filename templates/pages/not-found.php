<section class="not-found">
    <picture class="not-found-picture">
        <source media="(max-width: 47.99rem)" type="image/webp" srcset="<?= $this->asset('/assets/images/404-mobile-480.webp') ?> 480w, <?= $this->asset('/assets/images/404-mobile-800.webp') ?> 800w" sizes="calc(100vw - 40px)" width="800" height="800">
        <source media="(max-width: 47.99rem)" srcset="<?= $this->asset('/assets/images/404-mobile.jpg') ?>" width="800" height="800">
        <source type="image/webp" srcset="<?= $this->asset('/assets/images/404-800.webp') ?> 800w, <?= $this->asset('/assets/images/404-1280.webp') ?> 1280w" sizes="(min-width: 72rem) 600px, 50vw" width="1280" height="720">
        <img class="not-found-image" src="<?= $this->asset('/assets/images/404.png') ?>" width="1280" height="720" alt="Jaspion de sunga rosa, com a mão no capacete, confuso em uma paisagem espacial.">
    </picture>
    <div class="not-found-content">
        <p class="eyebrow"><span class="accent-line" aria-hidden="true"></span> Erro 404</p>
        <h1>Até o Jaspion<br>se perdeu aqui.</h1>
        <p>Página não encontrada. Talvez o artigo tenha mudado de endereço ou ainda não tenha sido publicado.</p>
        <a class="button" href="/">Voltar para os artigos <span aria-hidden="true">→</span></a>
    </div>
</section>
