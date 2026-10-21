import { spawn, spawnSync } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const directory = mkdtempSync(path.join(os.tmpdir(), 'pj-static-front-'));
const build = spawnSync('php', ['tests/Front/build-fixtures.php', directory], { stdio: 'inherit' });
if (build.status !== 0) {
    rmSync(directory, { recursive: true, force: true });
    process.exit(build.status ?? 1);
}
const server = spawn('php', ['-S', '127.0.0.1:8765', '-t', `${directory}/dist`, 'tests/Front/server.php'], {
    stdio: 'inherit', env: { ...process.env, PJ_FRONT_DIRECTORY: directory },
});
for (const signal of ['SIGINT', 'SIGTERM']) process.on(signal, () => server.kill(signal));
server.on('exit', (code) => {
    rmSync(directory, { recursive: true, force: true });
    process.exitCode = code ?? 0;
});
server.on('error', (error) => {
    console.error(error);
    rmSync(directory, { recursive: true, force: true });
    process.exitCode = 1;
});
