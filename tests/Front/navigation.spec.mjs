import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const routes = ['/', '/tags', '/tags/php', '/pagina/2', '/artigos/artigo-1', '/nao-existe', '/__empty'];

test('article, tags, pagination and recovery are real links', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('link', { name: /Um título longo/ }).click();
    await expect(page).toHaveURL(/\/artigos\/artigo-1$/);
    await expect(page.locator('h1')).toHaveCount(1);
    await page.getByRole('link', { name: 'PHP', exact: true }).click();
    await expect(page).toHaveURL(/\/tags\/php$/);
    await page.getByRole('link', { name: 'Mais antigos' }).click();
    await expect(page).toHaveURL(/\/tags\/php\/pagina\/2$/);
    await expect(page.getByText('Página 2 de 3')).toBeVisible();
    await page.getByRole('link', { name: 'Mais recentes' }).click();
    await expect(page).toHaveURL(/\/tags\/php$/);
    await page.getByRole('link', { name: /Todos os assuntos/ }).click();
    await expect(page).toHaveURL(/\/tags$/);
    await expect(page.getByRole('heading', { name: 'Todos os assuntos' })).toBeVisible();
    expect((await page.goto('/nao-existe')).status()).toBe(404);
    await page.getByRole('link', { name: /Voltar para os artigos/ }).click();
    await expect(page).toHaveURL('/');
});

test('theme follows system before the first click and then persists direct alternation', async ({ page }) => {
    await page.emulateMedia({ colorScheme: 'dark' });
    await page.goto('/');
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await expect(page.getByRole('button', { name: /Tema escuro/ })).toBeVisible();
    await expect(page.locator('.theme-icon-moon')).toBeVisible();
    await page.getByRole('button', { name: /Tema escuro/ }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
    await page.getByRole('button', { name: /Tema claro/ }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
});

test('blocked storage does not break theme controls', async ({ page }) => {
    await page.addInitScript(() => {
        Object.defineProperty(window, 'localStorage', { get() { throw new DOMException('Blocked', 'SecurityError'); } });
    });
    await page.emulateMedia({ colorScheme: 'dark' });
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/');
    await page.getByRole('button', { name: /Tema escuro/ }).click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
    expect(errors).toEqual([]);
});

test('reading and dark system theme work without JavaScript', async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, colorScheme: 'dark' });
    const page = await context.newPage();
    await page.goto('http://127.0.0.1:8765/');
    await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(28, 31, 29)');
    await expect(page.locator('.theme-toggle')).toBeHidden();
    await page.getByRole('link', { name: /Um título longo/ }).click();
    await expect(page.locator('.prose')).toContainText('Leitura e hierarquia');
    await context.close();
});

test('keyboard skip link moves focus to content', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Pular para o conteúdo' })).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('main')).toBeFocused();
});

test('code and tables scroll with the keyboard', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/artigos/artigo-1');
    for (const name of ['Bloco de código', 'Tabela']) {
        const region = page.getByRole('region', { name, exact: true });
        await region.focus();
        await expect(region).toBeFocused();
        await page.keyboard.press('ArrowRight');
        await expect.poll(() => region.evaluate((element) => element.scrollLeft)).toBeGreaterThan(0);
    }
});

test('small-screen controls have comfortable touch areas', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 740 });
    await page.goto('/');
    for (const control of await page.locator('.main-nav a, .brand, .theme-toggle, .tag, .button').all()) {
        const bounds = await control.boundingBox();
        expect(bounds.width).toBeGreaterThanOrEqual(44);
        expect(bounds.height).toBeGreaterThanOrEqual(44);
    }
});

for (const theme of ['light', 'dark']) {
    test(`mobile accessibility ${theme}`, async ({ page }) => {
        await page.setViewportSize({ width: 320, height: 740 });
        await page.emulateMedia({ colorScheme: theme, reducedMotion: 'reduce' });
        for (const route of ['/', '/tags', '/artigos/artigo-1', '/nao-existe']) {
            await page.goto(route);
            const scan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(scan.violations).toEqual([]);
        }
    });
    for (const route of routes) {
        test(`accessible ${theme} ${route}`, async ({ page }) => {
            await page.emulateMedia({ colorScheme: theme, reducedMotion: 'reduce' });
            await page.goto(route);
            await expect(page.locator('h1')).toHaveCount(1);
            await expect(page.locator('body')).toBeVisible();
            const scan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
            expect(scan.violations).toEqual([]);
        });
    }
}

for (const [width, height] of [[320, 740], [375, 812], [768, 1024], [1024, 768], [1440, 1000], [812, 375]]) {
    test(`responsive pages ${width}×${height}`, async ({ page }) => {
        await page.setViewportSize({ width, height });
        for (const route of routes) {
            await page.goto(route);
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
            await expect(page.getByRole('navigation', { name: 'Principal', exact: true })).toBeVisible();
        }
    });
}

test('200% text and zoom-equivalent reflow preserve content', async ({ page }) => {
    await page.setViewportSize({ width: 640, height: 450 });
    for (const route of ['/', '/tags', '/artigos/artigo-1', '/nao-existe']) {
        await page.goto(route);
        await page.addStyleTag({ content: 'html { font-size: 200%; }' });
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    }
});

test('SEO, assets and JSON-LD are delivered in HTML', async ({ page }) => {
    for (const route of ['/', '/tags', '/tags/php', '/pagina/2', '/tags/php/pagina/2', '/artigos/artigo-1']) {
        await page.goto(route);
        await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', `http://127.0.0.1:8765${route}`);
        const schema = JSON.parse(await page.locator('script[type="application/ld+json"]').textContent());
        expect(schema['@type']).toBe(route.startsWith('/artigos/') ? 'BlogPosting' : 'WebSite');
        await expect(page.locator('meta[property="og:image"]')).toHaveAttribute('content', /http.*banner-light\.png\?v=/);
        for (const image of await page.locator('img:visible').all()) {
            await image.scrollIntoViewIfNeeded();
            await expect.poll(() => image.evaluate((element) => element.complete && element.naturalWidth > 0)).toBe(true);
        }
    }
    await page.goto('/nao-existe');
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex, follow');
    await expect(page.locator('link[rel="canonical"]')).toHaveCount(0);
});

test('footer social links only appear when their destinations are configured', async ({ page }) => {
    await page.goto('/__with-social');
    for (const network of ['LinkedIn', 'GitHub', 'Instagram']) {
        const link = page.getByRole('link', { name: new RegExp(network) });
        await expect(link).toHaveAttribute('target', '_blank');
        await expect(link).toHaveAttribute('rel', /noopener/);
    }

    await page.goto('/__empty');
    await expect(page.getByRole('link', { name: /LinkedIn|GitHub|Instagram/ })).toHaveCount(0);
});
