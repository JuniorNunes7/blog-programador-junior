import sharp from 'sharp';
import { mkdir, readFile, stat } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const source = (name) => path.join(root, 'resources/images', name);
const output = (name) => path.join(root, 'public/assets', name);
await mkdir(output('images'), { recursive: true });
await mkdir(output('brand'), { recursive: true });

// Small exports use the same outlined master, never fonts installed on the build host.
for (const size of [16, 32, 180, 512, 1024]) {
    const name = size === 180 ? 'apple-touch-icon' : size < 100 ? `favicon-${size}` : `profile-${size}`;
    await sharp(output('brand/monogram-dark.svg'), { density: 192 })
        .resize(size, size).png().toFile(output(`brand/${name}.png`));
}
for (const theme of ['light', 'dark']) {
    let background = sharp(source('editorial-paper.png')).resize(1200, 630, { fit: 'cover' });
    if (theme === 'dark') {
        const veil = Buffer.from('<svg width="1200" height="630"><rect width="1200" height="630" fill="#1C1F1D" fill-opacity=".88"/></svg>');
        background = sharp(await background.composite([{ input: veil }]).png().toBuffer());
    }
    const type = await readFile(path.join(root, `resources/brand/banner-type-${theme}.svg`));
    await background.composite([{ input: type }]).png({ palette: true, quality: 95 }).toFile(output(`brand/banner-${theme}.png`));
}
for (const width of [640, 960]) {
    await sharp(source('editorial-paper.png')).resize(width, Math.round(width * 9 / 8), { fit: 'cover', position: 'right' })
        .webp({ quality: 80 }).toFile(output(`images/editorial-paper-${width}.webp`));
}
await sharp(source('editorial-paper.png')).resize(640, 720, { fit: 'cover', position: 'right' }).jpeg({ quality: 80, mozjpeg: true }).toFile(output('images/editorial-paper.jpg'));
for (const width of [800, 1280]) {
    await sharp(source('404-original.png')).resize(width).webp({ quality: 78 }).toFile(output(`images/404-${width}.webp`));
}
for (const width of [480, 800]) {
    await sharp(source('404-mobile.png')).resize(width, width, { fit: 'cover' }).webp({ quality: 78 }).toFile(output(`images/404-mobile-${width}.webp`));
}
await sharp(source('404-mobile.png')).resize(800, 800).jpeg({ quality: 78, mozjpeg: true }).toFile(output('images/404-mobile.jpg'));
await sharp(source('404-original.png')).resize(1280, 720).png({ palette: true, quality: 80, effort: 10 }).toFile(output('images/404.png'));
for (const file of ['404.png', '404-1280.webp', '404-mobile-800.webp']) {
    console.log(`${file}: ${Math.round((await stat(output(`images/${file}`))).size / 1024)} KiB`);
}
