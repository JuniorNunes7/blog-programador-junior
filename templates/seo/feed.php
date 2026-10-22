<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
    <channel>
        <title><?= $this->escape($site['name']) ?></title>
        <link><?= $this->escape($siteUrl) ?></link>
        <description><?= $this->escape($site['description']) ?></description>
        <language>pt-BR</language>
        <?php foreach ($items as $item) : ?>
            <item>
                <title><?= $this->escape($item['title']) ?></title>
                <link><?= $this->escape($item['url']) ?></link>
                <guid isPermaLink="true"><?= $this->escape($item['url']) ?></guid>
                <description><?= $this->escape($item['description']) ?></description>
                <pubDate><?= $this->escape($item['publishedAt']) ?></pubDate>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>
