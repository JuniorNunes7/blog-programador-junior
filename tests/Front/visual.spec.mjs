import { test, expect } from '@playwright/test';

for (const theme of ['light', 'dark']) {
    for (const width of [375, 1440]) {
        for (const [name, route] of Object.entries({ home: '/', tag: '/tags/php', article: '/artigos/artigo-1', error: '/nao-existe', empty: '/__empty', pagination: '/pagina/2' })) {
            test(`${name}-${theme}-${width}`, async ({ page }) => {
                await page.setViewportSize({ width, height: 1000 });
                await page.emulateMedia({ colorScheme: theme, reducedMotion: 'reduce' });
                await page.goto(route);
                await page.evaluate(() => document.fonts.ready);
                await expect(page).toHaveScreenshot(`${name}-${theme}-${width}.png`, { fullPage: true, animations: 'disabled' });
            });
        }
    }
}
