import { execFileSync } from 'node:child_process';
import { appendFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';

export function changedFiles(base, head) {
    if (![base, head].every((ref) => /^[a-f0-9]{40,64}$/.test(ref ?? ''))) {
        throw new Error('Informe os SHAs completos da base e do head do PR.');
    }

    // Treat renames as deletion + addition so both the old and new paths count.
    return execFileSync('git', ['diff', '--name-only', '--no-renames', '-z', `${base}...${head}`, '--'], {
        encoding: 'utf8',
    }).split('\0').filter(Boolean);
}

export function classifyChanges(files) {
    const codeDirectories = /^(src|templates|public|resources|tests|bin|config|\.github)\//;
    const codeFiles = /^(composer\.(json|lock)|package(-lock)?\.json|phpunit\.xml|phpcs\.xml|playwright\.config\.mjs|\.env\.example|\.markdownlint\.json)$/;
    return {
        code: files.some((file) => !file.endsWith('.md') && (
            codeDirectories.test(file) || codeFiles.test(file) || /\.(php|css|js|mjs|cjs|html)$/.test(file)
        )),
        markdown: files.some((file) => file.endsWith('.md')),
        articles: files.some((file) => /^content\/articles\/[^/]+\.md$/.test(file)),
    };
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
    const changes = classifyChanges(changedFiles(...process.argv.slice(2)));
    const output = Object.entries(changes).map(([key, value]) => `${key}=${value}\n`).join('');
    if (process.env.GITHUB_OUTPUT) appendFileSync(process.env.GITHUB_OUTPUT, output);
    process.stdout.write(output);
}
