<?php

declare(strict_types=1);

namespace Tests\Content;

use Blog\Content\Errors\ContentException;
use Blog\Content\Infrastructure\FrontMatterParser;
use PHPUnit\Framework\TestCase;

final class FrontMatterParserTest extends TestCase
{
    public function testItParsesRequiredMetadataAndMarkdown(): void
    {
        $article = (new FrontMatterParser())->parse('meu-artigo', <<<'MARKDOWN'
---
title: Meu artigo
date: 2026-10-18
description: Uma descrição para buscadores.
keywords:
  - php
  - markdown
tags:
  - arquitetura
---

# Conteúdo
MARKDOWN);

        self::assertSame('meu-artigo', $article->metadata->slug);
        self::assertSame('Meu artigo', $article->metadata->title);
        self::assertSame(['php', 'markdown'], $article->metadata->keywords);
        self::assertSame('# Conteúdo', $article->markdown);
    }

    public function testItRejectsMissingRequiredMetadata(): void
    {
        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('keywords');

        (new FrontMatterParser())->parse('meu-artigo', <<<'MARKDOWN'
---
title: Meu artigo
date: 2026-10-18
description: Uma descrição
tags:
  - php
---

Conteúdo
MARKDOWN);
    }

    public function testItRejectsAnInvalidDate(): void
    {
        $this->expectException(ContentException::class);
        $this->expectExceptionMessage('YYYY-MM-DD');

        (new FrontMatterParser())->parse('meu-artigo', <<<'MARKDOWN'
---
title: Meu artigo
date: 18/10/2026
description: Uma descrição
keywords:
  - php
tags:
  - php
---

Conteúdo
MARKDOWN);
    }
}
