# Programador Junior

Blog editorial em PHP, orientado por artigos Markdown e sem CMS. A aplicação não usa banco de dados: os textos ficam em `content/articles/` e um índice JSON é gerado durante a publicação.

## Requisitos

- PHP 8.5+
- Composer 2+

## Instalação local

```bash
composer install
php bin/build-index.php
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

Depois de criar ou alterar artigos, execute `php bin/build-index.php`. O comando valida todos os arquivos e só substitui `storage/article-index.json` depois de gerar um resultado válido.

## Publicação em Apache

Configure o `DocumentRoot` do virtual host para a pasta `public/`. O arquivo `.htaccess` já encaminha URLs amigáveis ao front controller e mantém arquivos estáticos fora dessa regra. Antes do deploy, defina a URL pública final em `config/site.php`, execute `composer install --no-dev --optimize-autoloader` e depois `php bin/build-index.php`.

O site disponibiliza `/sitemap.xml`, `/feed.xml` e `/robots.txt` automaticamente. Artigos recebem URL canônica, metatags de compartilhamento, JSON-LD `BlogPosting`, `ETag` e `Last-Modified`.

## Front e desenvolvimento

As decisões visuais ficam nas [diretrizes de marca](docs/design/BRAND.md), e o conteúdo segue o [guia editorial](docs/EDITORIAL.md). O HTML é renderizado no servidor; navegação e leitura funcionam sem JavaScript. O JavaScript aplica e persiste apenas a preferência de tema.

- `templates/components/`: marca, cabeçalho, rodapé, head/SEO, temas, cards, tags, metadados e paginação.
- `templates/pages/`: composição de listagens, artigo e página não encontrada.
- `public/assets/css/`: tokens, base, componentes compartilhados e estilos por página.
- `public/assets/js/`: inicialização do tema antes da pintura e controle de preferência.
- `src/Web/ArticleRenderer.php`: apresentação do Markdown, títulos, imagens locais e blocos roláveis acessíveis.
- `resources/`: matrizes de imagens, fontes licenciadas e tipografia vetorial usada nas exportações. Não publicar essa pasta como DocumentRoot.

Para iniciar o site localmente depois de gerar o índice:

```sh
php -S 127.0.0.1:8000 -t public bin/serve.php
```

Node 24 e npm são necessários somente para desenvolvimento. Não há bundler, framework de front ou processo Node em produção. Os arquivos públicos gerados ficam versionados.

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

A suíte inicia um servidor PHP de fixtures na porta 8765. Seus artigos e índices são criados no diretório temporário do sistema, sem alterar `content/articles/` ou o índice real. A rota `/__empty` existe somente nesse servidor de testes.

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
npm run test:front:container
git diff --check
```

A URL pública de SEO vem de `BLOG_BASE_URL` no ambiente do processo PHP (ou de `config/site.php`). Os links opcionais do rodapé usam `BLOG_LINKEDIN_URL`, `BLOG_GITHUB_URL` e `BLOG_INSTAGRAM_URL`; um valor ausente mantém a respectiva rede oculta. O bootstrap carrega `.env` quando ele existe, mas variáveis já definidas pelo servidor têm prioridade. Reinicie o processo PHP depois de editar o arquivo. Defina o domínio e as URLs reais antes do deploy. `BLOG_AUTHOR` segue a mesma regra.
