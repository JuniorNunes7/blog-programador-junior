<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($urls as $url) : ?>
        <url>
            <loc><?= $this->escape($url['url']) ?></loc>
            <?php if (isset($url['lastModified'])) : ?>
                <lastmod><?= $this->escape($url['lastModified']) ?></lastmod>
            <?php endif; ?>
        </url>
    <?php endforeach; ?>
</urlset>
