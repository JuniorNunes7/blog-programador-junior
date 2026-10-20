# Guia para agentes

## Fontes de verdade

- Para instalar o projeto, publicar alterações e entender a estrutura do blog, leia o [README](README.md).
- Antes de criar ou revisar artigos, siga o [guia editorial](docs/EDITORIAL.md).
- Antes de alterar interface, identidade visual ou assets de marca, siga as [diretrizes de marca](docs/design/BRAND.md) e consulte o [painel visual](docs/design/brand-guidelines.png) apenas como referência de composição.

Não duplique essas diretrizes em novos documentos ou respostas: mantenha cada assunto na sua fonte de verdade e crie links quando houver relação entre eles.

## Alterações no projeto

- Artigos vivem em `content/articles/` e precisam respeitar o front matter descrito no [README](README.md).
- Depois de alterar artigos, gere o índice com `composer build:index`.
- Para alterações em PHP, execute `composer test` e `composer lint` quando forem pertinentes.
- Preserve o site sem banco de dados e não introduza um CMS ou dependências de execução sem solicitação explícita.
