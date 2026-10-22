import { execFileSync } from 'node:child_process';
import { lstatSync, readFileSync } from 'node:fs';
import { lint } from 'markdownlint-cli2/markdownlint/promise';
import { changedFiles } from './pr-changes.mjs';

const args = process.argv.slice(2);
const files = args[0] === '--diff'
    ? changedFiles(args[1], args[2]).filter((file) => file.endsWith('.md') && lstatSync(file, { throwIfNoEntry: false })?.isFile())
    : args.length ? args : execFileSync('git', ['ls-files', '-z'], { encoding: 'utf8' }).split('\0').filter((file) => file.endsWith('.md'));

if (files.length === 0) {
    console.log('Nenhum Markdown existente para validar.');
} else {
    // Pass literal filenames to the library: no shell interpolation or glob expansion.
    const config = JSON.parse(readFileSync(new URL('../.markdownlint.json', import.meta.url), 'utf8'));
    const results = await lint({ files, config });
    const errors = Object.entries(results).flatMap(([file, issues]) => issues.map((issue) =>
        `${file}:${issue.lineNumber} ${issue.ruleNames[0]} ${issue.ruleDescription}${issue.errorDetail ? ` (${issue.errorDetail})` : ''}`
    ));
    if (errors.length) {
        console.error(errors.join('\n'));
        process.exitCode = 1;
    } else {
        console.log(`${files.length} arquivo(s) Markdown validado(s).`);
    }
}
