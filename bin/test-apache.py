#!/usr/bin/env python3
"""Exercise the published artifact using an isolated Apache with no PHP module."""

import http.client
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parent.parent
apache = shutil.which('httpd') or shutil.which('apache2')
if not apache:
    raise SystemExit('Instale Apache 2.4 para executar este teste.')
modules = next((p for p in [Path('/usr/lib64/httpd/modules'), Path('/usr/lib/apache2/modules')]
                if p.is_dir()), None)
if modules is None:
    raise SystemExit('Diretório de módulos Apache não encontrado.')
compiled = subprocess.check_output([apache, '-l'], text=True)

with tempfile.TemporaryDirectory(prefix='pj-apache-') as temporary:
    directory = Path(temporary)
    directory.chmod(0o755)
    subprocess.run(['php', 'tests/Front/build-fixtures.php', str(directory)], cwd=ROOT, check=True)
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    loaded = []
    for name in ['mpm_event', 'authz_core', 'unixd', 'dir', 'mime', 'rewrite', 'headers']:
        if f'mod_{name}.c' not in compiled:
            loaded.append(f'LoadModule {name}_module "{modules}/mod_{name}.so"')
    identity = 'User daemon\nGroup daemon' if os.getuid() == 0 else ''
    config = directory / 'httpd.conf'
    config.write_text('\n'.join(loaded) + f'''
ServerRoot "{directory}"
ServerName 127.0.0.1
Listen 127.0.0.1:{port}
PidFile "{directory}/httpd.pid"
ErrorLog "{directory}/error.log"
LogLevel warn
{identity}
TypesConfig /etc/mime.types
DocumentRoot "{directory}/dist"
<Directory "{directory}/dist">
    AllowOverride All
    Require all granted
</Directory>
''')
    # Fedora keeps the MIME database alongside httpd.conf.
    if not Path('/etc/mime.types').is_file():
        config.write_text(config.read_text().replace('/etc/mime.types', '/etc/httpd/conf/mime.types'))
    subprocess.run([apache, '-t', '-f', str(config)], check=True)
    server = subprocess.Popen([apache, '-f', str(config), '-DFOREGROUND'], stdout=subprocess.DEVNULL)
    checks = 0

    def request(path, status=200, method='GET'):
        global checks
        connection = http.client.HTTPConnection('127.0.0.1', port, timeout=5)
        connection.request(method, path)
        response = connection.getresponse()
        body = response.read()
        headers = dict((key.lower(), value) for key, value in response.getheaders())
        connection.close()
        assert response.status == status, (path, response.status, status, body[:300])
        checks += 1
        return headers, body

    try:
        for attempt in range(50):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=0.2):
                    break
            except OSError:
                if server.poll() is not None:
                    raise RuntimeError('Apache encerrou antes de iniciar.')
                time.sleep(0.1)
        for route in ['/', '/tags', '/tags/php', '/pagina/2', '/tags/php/pagina/2', '/artigos/artigo-1']:
            headers, body = request(route)
            assert headers['content-type'].startswith('text/html'), headers
            assert headers['cache-control'] == 'public, max-age=300', headers
            assert 'etag' in headers and 'last-modified' in headers, headers
            assert b'<html' in body and b'<?php' not in body
            head, empty = request(route, method='HEAD')
            assert empty == b'' and head['content-length'] == str(len(body)), (route, head)
        for original, canonical in [
            ('/index.html', '/'), ('/pagina/1', '/'), ('/tags/php/pagina/1', '/tags/php'),
            ('/tags.html', '/tags'), ('/tags/', '/tags'), ('/tags/php/', '/tags/php'),
            ('/artigos/artigo-1.html', '/artigos/artigo-1'),
            ('/artigos/artigo-1/', '/artigos/artigo-1'),
            ('/artigos/artigo-1.html?source=test', '/artigos/artigo-1?source=test'),
        ]:
            headers, _ = request(original, 301)
            assert headers['location'] == f'http://127.0.0.1:{port}{canonical}', headers
        for route in ['/missing', '/missing.xml', '/pagina/999', '/tags/missing', '/assets/missing.png',
                      '/index.php', '/artigos', '/content/articles/artigo-1.md', '/storage/build-manifest.json',
                      '/404', '/404.html', '/.htaccess']:
            _, body = request(route, 404)
            assert 'Página não encontrada'.encode() in body
            _, empty = request(route, 404, method='HEAD')
            assert empty == b''
        for route, mime in [('/sitemap.xml', 'application/xml'), ('/feed.xml', 'application/rss+xml'),
                            ('/robots.txt', 'text/plain')]:
            headers, body = request(route)
            assert headers['content-type'].startswith(mime), headers
            assert headers['cache-control'] == 'public, max-age=3600', headers
            if route.endswith('.xml'):
                ET.fromstring(body)
        for route, mime in [('/assets/css/base.css', 'text/css'), ('/assets/js/theme.js', 'application/javascript'),
                            ('/assets/brand/favicon.svg', 'image/svg+xml'),
                            ('/assets/images/editorial-paper-640.webp', 'image/webp'),
                            ('/assets/fonts/manrope-500.woff2', 'font/woff2')]:
            headers, body = request(route)
            assert headers['content-type'].startswith(mime), headers
            assert body
        print(f'Apache sem PHP: {checks} requisições verificadas com sucesso.')
    except BaseException:
        if (directory / 'error.log').exists():
            print((directory / 'error.log').read_text())
        raise
    finally:
        server.terminate()
        server.wait(timeout=10)
