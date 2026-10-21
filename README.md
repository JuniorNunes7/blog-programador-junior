# Programador Junior

Blog editorial estático, gerado por PHP a partir de artigos Markdown e templates. Os textos ficam em `content/articles/`; `composer build` produz o site completo em `dist/`. A hospedagem entrega arquivos prontos, sem PHP, Composer, banco de dados ou CMS.

## Requisitos

Para gerar o site:

- PHP 8.5+
- Composer 2+

Para hospedar: Apache 2.4 com `mod_rewrite`, `mod_dir`, `mod_mime` e `mod_headers`, permitindo as diretivas do `.htaccess`. PHP não é necessário na hospedagem.

## Instalação local

```bash
composer install
composer build
```

## Escrevendo um artigo

Crie um arquivo em `content/articles/` com nome em minúsculas e hífens. O nome vira a URL do artigo. Cada arquivo precisa conter front matter YAML simples e os campos `title`, `date`, `description`, `keywords` e `tags`.

```md
---
title: Um título claro
date: 2026-10-18
description: Resumo usado por mecanismos de busca.
keywords:
  - php
  - markdown
tags:
  - arquitetura
---

# Conteúdo do artigo
```

Depois de criar ou alterar artigos, execute `composer build`. O nome do arquivo continua definindo a URL, por exemplo `/artigos/ola-mundo`. Somente os arquivos `.md` diretamente em `content/articles/` são artigos; README e outros documentos não são publicados.

## Geração incremental

```sh
composer build
composer build -- --full
```

O comando calcula SHA-256 dos artigos e das entradas globais. Artigos novos ou alterados são validados e renderizados; artigos intactos reaproveitam o HTML anterior. Qualquer inclusão, alteração, exclusão ou renomeação atualiza home, tags, paginações, sitemap e RSS. Páginas que deixam de existir são removidas da saída. Templates, código, dependências, configuração efetiva e assets alterados provocam reconstrução completa.

O manifesto privado `storage/build-manifest.json` guarda hashes, metadados dos artigos e o inventário da saída. Configurações de ambiente entram somente no hash global. Sem mudanças e com a saída íntegra, nenhum arquivo do site ou manifesto é reescrito. Manifesto ausente/inválido ou saída ausente/alterada provoca reconstrução completa. `--full` força esse comportamento.

O build usa um lock para impedir execuções simultâneas, prepara e verifica o resultado em uma pasta temporária e depois substitui `dist/`. Falhas de validação ou preparação preservam o build anterior; falhas na troca restauram a versão anterior quando possível. Se uma interrupção deixar somente `dist.previous/`, o próximo build a recupera. Não execute a geração dentro da pasta servida em produção: a troca local de diretórios não é um mecanismo de deploy sem interrupção.

`dist/` e o manifesto são descartáveis, ignorados pelo Git e não devem receber edições manuais. Os assets de origem continuam versionados em `public/assets/`. O comando legado `composer build:index` permanece disponível para gerar `storage/article-index.json`, mas não é requisito para o site estático.

## Publicação em Apache

Gere o site fora da pasta atendida pela hospedagem, com a configuração de produção:

```sh
composer install --no-dev --optimize-autoloader
BLOG_BASE_URL=https://seu-dominio.example composer build
```

Publique **somente o conteúdo de `dist/`**, incluindo `.htaccess`, e configure o `DocumentRoot` para essa cópia publicada. A publicação precisa substituir a versão anterior, removendo arquivos que não estão no novo artefato; apenas sobrepor arquivos pode manter artigos excluídos acessíveis. A publicação permanece manual. Não envie `content/`, `templates/`, `src/`, `vendor/`, `.env` ou `storage/`.

O `.htaccess` resolve URLs sem extensão para HTML existente, normaliza os aliases `.html`, barra final e página 1, e entrega a página personalizada com status 404 em caminhos inexistentes. A configuração do virtual host deve permitir essas diretivas (por exemplo, `AllowOverride All` limitado ao diretório publicado). Outro servidor estático precisa de regras equivalentes: `.htaccess` é específico do Apache.

O artefato inclui `/sitemap.xml`, `/feed.xml` e `/robots.txt`. Artigos preservam URL canônica, metatags de compartilhamento e JSON-LD `BlogPosting`. Apache fornece `ETag` e `Last-Modified` dos arquivos gerados; o cache de HTML é de cinco minutos, XML/texto de uma hora e os assets mantêm a política definida no `.htaccess`.

## Front e desenvolvimento

As decisões visuais ficam nas [diretrizes de marca](docs/design/BRAND.md), e o conteúdo segue o [guia editorial](docs/EDITORIAL.md). O HTML é renderizado durante o build; navegação e leitura funcionam sem JavaScript. O JavaScript aplica e persiste apenas a preferência de tema.

- `templates/components/`: marca, cabeçalho, rodapé, head/SEO, temas, cards, tags, metadados e paginação.
- `templates/pages/`: composição de listagens, artigo e página não encontrada.
- `public/assets/css/`: tokens, base, componentes compartilhados e estilos por página.
- `public/assets/js/`: inicialização do tema antes da pintura e controle de preferência.
- `src/Web/ArticleRenderer.php`: apresentação do Markdown, títulos, imagens locais e blocos roláveis acessíveis.
- `resources/`: matrizes de imagens, fontes licenciadas e tipografia vetorial usada nas exportações. Não publicar essa pasta como DocumentRoot.

Para visualizar o site localmente depois de gerar `dist/`:

```sh
php -S 127.0.0.1:8000 -t dist bin/serve.php
```

A prévia local apenas lê os arquivos gerados; alterações exigem novo `composer build`. Ela não substitui a validação do `.htaccess` em Apache.

Node 24 e npm são necessários somente para desenvolvimento. Não há bundler, framework de front ou processo Node em produção. As variantes de assets ficam versionadas; o site montado em `dist/` não fica.

```sh
npm ci
npm run build:assets
```

Playwright automatiza navegação, screenshots e testes nos três motores de navegador; axe-core verifica regras de acessibilidade; Sharp gera os tamanhos e formatos das imagens. Essas dependências são exclusivamente de desenvolvimento.

## Assets

Os logotipos e monogramas portáteis, favicons, ícone Apple, imagens de perfil e banners claro/escuro ficam em `public/assets/brand/`. Os banners têm 1200 × 630 px. A home e a 404 usam variantes responsivas em `public/assets/images/`; o original da 404 está preservado em `resources/images/404-original.png`.

`npm run build:assets` gera PNG, JPEG e WebP a partir das matrizes e dos SVGs em curvas. O script não chama serviços externos. A origem e os prompts das duas artes estão em [resources/images/README.md](resources/images/README.md).

As fontes WOFF2 são locais, com suporte aos caracteres de português e licenças em `public/assets/fonts/`. Os SVGs não dependem de fontes instaladas na máquina. Apenas para alterar fontes ou reconstruir as curvas:

```sh
python3 -m venv /tmp/pj-font-tools
/tmp/pj-font-tools/bin/pip install 'fonttools[woff]==4.67.0'
/tmp/pj-font-tools/bin/python bin/build-brand.py
npm run build:assets
```

O pacote Python é uma ferramenta opcional de autoria, não uma dependência de instalação ou publicação do site.

## Testes de front

```sh
npm ci
npx playwright install --with-deps chromium firefox webkit
npm run test:front -- --project=chromium --project=firefox --project=webkit
```

A suíte gera sites de fixtures no diretório temporário do sistema antes de iniciar o servidor na porta 8765. O servidor apenas entrega os arquivos estáticos, sem renderizar artigos por requisição e sem alterar o conteúdo ou a saída reais. As rotas `/__empty`, `/__single` e `/__with-social` existem somente nessas fixtures.

As capturas de referência são geradas com Chromium no container oficial Playwright 1.64.1 / Ubuntu Noble, fixado por digest. Para reproduzir exatamente a CI, use Linux com Docker disponível e a porta 8765 livre:

```sh
npm run test:front:container
```

Para atualizar as referências após uma alteração visual intencional:

```sh
npm run test:front:container -- --project=visual --update-snapshots
```

Revise as imagens de `tests/Front/visual.spec.mjs-snapshots/` antes de aceitar mudanças. Capturas geradas diretamente no host podem diferir por versão do sistema e renderização de fontes. Relatórios HTML, traces e capturas de falhas ficam em `playwright-report/` e `test-results/`, ignorados pelo Git. A CI preserva esses relatórios quando um teste falha.

A cobertura inclui os dois temas, conteúdo sem JavaScript, armazenamento bloqueado, navegação por teclado, tags, paginação, 404, estado vazio, metadados e larguras entre 320 e 1440 px, incluindo orientação horizontal e reflow equivalente a zoom com texto ampliado. Complete a automação com inspeção visual e verificação de zoom real no navegador; axe-core não certifica sozinho a acessibilidade do site.

Antes de entregar alterações:

```sh
composer test
composer lint
composer build
composer test:apache
npm run test:front:container
git diff --check
```

`composer test:apache` requer Python 3 e Apache 2.4 locais (pacote `apache2` no Ubuntu ou `httpd` no Fedora). Ele gera fixtures e inicia uma instância isolada, em porta temporária, carregando apenas módulos de entrega estática. Valida URLs, redirects, 404, paginação, MIME types, cache e GET/HEAD sem PHP no servidor. A CI executa essa validação além dos testes PHP e de front.

A URL pública de SEO vem de `BLOG_BASE_URL` no ambiente do processo de build (ou de `config/site.php`). Os links opcionais do rodapé usam `BLOG_LINKEDIN_URL`, `BLOG_GITHUB_URL` e `BLOG_INSTAGRAM_URL`; um valor ausente mantém a respectiva rede oculta. O bootstrap carrega `.env` quando ele existe, mas variáveis já definidas no ambiente têm prioridade. Execute `composer build` e publique novamente depois de editar a configuração. Defina o domínio e as URLs reais antes do deploy. `BLOG_AUTHOR` segue a mesma regra.
