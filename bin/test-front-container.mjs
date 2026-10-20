import { spawn } from 'node:child_process';
import { createWriteStream } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import os from 'node:os';

// Same browser binaries and system libraries locally and in CI.
const image = 'mcr.microsoft.com/playwright:v1.64.1-noble@sha256:7852dc5a7d72f5fe458a597d690f6c42fbc8e9e26ddfa95f8e6ef552558d9bbf';
const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const log = createWriteStream(path.join(os.tmpdir(), 'pj-front-container-server.log'));
const server = spawn('php', ['-S', '127.0.0.1:8765', '-t', 'public', 'tests/Front/server.php'], {
    cwd: root, stdio: ['ignore', 'ignore', 'pipe'],
});
server.stderr.pipe(log);
let container;

function stop() {
    container?.kill('SIGTERM');
    server.kill('SIGTERM');
    log.end();
}
process.on('SIGINT', stop);
process.on('SIGTERM', stop);

try {
    let ready = false;
    for (let attempt = 0; attempt < 30; attempt++) {
        if (server.exitCode !== null) throw new Error('PHP server exited; inspect /tmp/pj-front-container-server.log');
        try {
            ready = (await fetch('http://127.0.0.1:8765')).ok;
        } catch { /* Wait for the server to bind its port. */ }
        if (ready) break;
        await new Promise((resolve) => setTimeout(resolve, 200));
    }
    if (!ready) throw new Error('Fixture server did not become ready.');
    container = spawn('docker', [
        'run', '--rm', '--init', '--network', 'host', '--ipc', 'host',
        '--security-opt', 'label=disable',
        '--user', `${process.getuid()}:${process.getgid()}`,
        '-e', 'PLAYWRIGHT_EXTERNAL_SERVER=1', '-e', 'CI=1',
        '-v', `${root}:/work`, '-w', '/work', image,
        'npx', 'playwright', 'test', ...process.argv.slice(2),
    ], { stdio: 'inherit' });
    process.exitCode = await new Promise((resolve, reject) => {
        container.once('error', reject);
        container.once('exit', (code) => resolve(code ?? 1));
    });
} finally {
    stop();
}
