# Blog Programador Júnior

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
