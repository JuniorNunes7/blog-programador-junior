# Programador Junior — Brand Guidelines

Versão: 1.0  
Data: 18 de outubro de 2026  
Escopo: identidade visual do blog e suas aplicações digitais.

## 1. Como usar este documento

Este arquivo traduz a identidade visual em regras legíveis por pessoas e LLMs. Use-o junto do [guia editorial](../EDITORIAL.md) e do [painel visual](brand-guidelines.png).

- Este Markdown é a referência para códigos de cor, fontes e regras de implementação.
- O PNG orienta composição, atmosfera e hierarquia; não é um layout para reproduzir pixel a pixel.
- O [guia editorial](../EDITORIAL.md) governa voz, conteúdo, autoria e responsabilidade editorial.
- Em divergências visuais entre o PNG e este arquivo, prevalecem os valores deste arquivo. Não extraia cores da textura do PNG.
- As decisões de marca abaixo consolidam a direção escolhida. Medidas de layout, estados e comportamento responsivo são padrões iniciais propostos para implementação, ajustáveis conforme o conteúdo.
- Este documento não fornece arquivos de fonte nem logotipos vetoriais prontos.

## 2. Essência da marca

**Nome:** Programador Junior.  
**Autor:** Junior Nunes.  
**Assinatura:** Menos ego. Mais aprendizado.  
**Conceito visual:** um caderno aberto de quem continua aprendendo.

O nome combina o nome do autor com o nível de senioridade. A experiência convive com a curiosidade e a disposição de rever decisões.

A identidade deve comunicar proximidade, maturidade técnica, reflexão e informalidade. O caderno representa hipóteses, anotações, revisões e aprendizados. A estética tem influência de publicações independentes: tipografia forte, espaço, tinta, papel e pequenos gestos manuais.

Evitar aparência de curso, guru, portal de notícias ou empresa genérica de tecnologia. Não transformar o erro em celebração de descuido; o foco é aprendizado responsável.

## 3. Logotipo e monograma

### Escrita

- Em textos: `Programador Junior`, sem acento em Junior.
- No logotipo: `programador junior_`, em minúsculas.
- No monograma: `pj_`.
- Assinatura exata: `Menos ego. Mais aprendizado.`
- O underscore faz parte do desenho da marca, não precisa ser lido por tecnologias assistivas.

### Construção tipográfica

Use Manrope. Na composição final, `programador` tem peso 500 e `junior` peso 800. O contraste de peso enfatiza o nome. O underscore é um detalhe terracota estático.

| Variante | Uso |
| --- | --- |
| Empilhada | Capas, apresentações e espaços com altura disponível |
| Horizontal | Cabeçalho do blog e assinaturas compactas |
| Monograma | Perfis, favicon e espaços pequenos |
| Monocromática | Impressão simples ou aplicações de uma única cor |

No tema claro, usar grafite no nome; petróleo pode destacar `junior` em aplicações maiores. No tema escuro, usar marfim em `programador` e sálvia em `junior`. Em ambos, usar a terracota correspondente ao tema no underscore.

Na versão monocromática, todas as partes, inclusive o underscore, usam a mesma cor. As pequenas variações do painel são ilustrativas.

### Proteção e redução

- Reservar ao redor da marca pelo menos a altura do `j` da própria composição.
- Não esticar, inclinar, aplicar contorno, brilho, sombra pesada ou degradê.
- Retirar a assinatura quando ela perder legibilidade.
- Usar monograma em vez do nome completo em espaços estreitos.
- Para favicon de 16–32 px, simplificar texturas e ajustar espaçamento; verificar a leitura no tamanho real.
- Produzir o logotipo em SVG ou texto HTML/CSS. Nunca recortar o painel para criar o logo.
- SVG com texto depende da fonte; para arquivo portátil, converter a tipografia em curvas após finalizar o desenho.
- Nome acessível do link da marca: `Programador Junior — início`.

## 5. Paleta clara

| Nome | HEX | Papel |
| --- | --- | --- |
| Papel | `#F6F1E7` | Fundo principal |
| Grafite | `#252724` | Texto principal e marca |
| Petróleo | `#245C53` | Links, ações e identidade |
| Terracota | `#B84B2D` | Sublinhados, chamadas e detalhes |
| Mostarda | `#D5AA45` | Fundos de etiquetas e acentos pontuais |

Como referência de composição, usar aproximadamente 70% de neutros, 20% de petróleo e 10% de acentos. Não é uma regra matemática para cada tela.

Usar grafite sobre mostarda. Não usar mostarda para texto pequeno sobre papel. Não atribuir uma cor exclusiva obrigatória a cada categoria.

## 6. Paleta escura fosca

| Nome | HEX | Papel |
| --- | --- | --- |
| Fundo | `#1C1F1D` | Fundo principal, grafite quente |
| Superfície | `#262B27` | Cards, menus e painéis |
| Elevado | `#303731` | Superfícies sobrepostas e estados sutis |
| Texto | `#E8E4DA` | Texto principal e marca |
| Secundário | `#B4B9AF` | Descrições e metadados |
| Sálvia | `#91B5A5` | Links, ações e identidade |
| Terracota | `#D58B70` | Destaques e sublinhados |
| Mostarda | `#C6AD72` | Etiquetas e acentos pontuais |

O acabamento fosco vem de baixa saturação, superfícies planas e ausência de brilho. A textura é opcional e decorativa. Não usar preto absoluto ou branco puro como grandes superfícies padrão. Não inverter automaticamente as cores do tema claro.

## 7. Tokens de implementação

As cores principais são as definidas acima. Valores auxiliares de superfície, texto secundário e borda no tema claro são complementos técnicos propostos, não novas cores de destaque da marca.

```css
:root {
  color-scheme: light;
  --bg: #F6F1E7;
  --surface: #EEE8DD;
  --surface-raised: #FFFCF6;
  --text: #252724;
  --text-muted: #5E635C;
  --brand: #245C53;
  --accent: #B84B2D;
  --support: #D5AA45;
  --border: #D2CEC4;
  --control-border: #757A71;
  --on-brand: #F6F1E7;
  --on-accent: #F6F1E7;
  --on-support: #252724;
  --focus: #245C53;

  --font-heading: "Manrope", system-ui, sans-serif;
  --font-body: "Source Sans 3", system-ui, sans-serif;
  --font-code: "IBM Plex Mono", ui-monospace, monospace;
  --content-width: 68ch;
  --page-width: 72rem;
  --radius-card: 0.5rem;
  --radius-control: 0.375rem;
}

[data-theme="dark"] {
  color-scheme: dark;
  --bg: #1C1F1D;
  --surface: #262B27;
  --surface-raised: #303731;
  --text: #E8E4DA;
  --text-muted: #B4B9AF;
  --brand: #91B5A5;
  --accent: #D58B70;
  --support: #C6AD72;
  --border: #464E47;
  --control-border: #7E897F;
  --on-brand: #1C1F1D;
  --on-accent: #1C1F1D;
  --on-support: #1C1F1D;
  --focus: #91B5A5;
}
```

`--border` serve para divisões decorativas; não deve ser a única indicação de um controle. Usar `--control-border` quando o contorno for necessário para identificar um campo ou botão.

Usar tokens semânticos nos componentes, evitando cores fixas espalhadas no código. Não usar `opacity` no contêiner de texto para obter cores secundárias.

### Seleção de tema

Padrão inicial proposto: seguir a preferência do sistema na primeira visita, oferecer alternância explícita e persistir a escolha local do visitante. Aplicar `data-theme` no elemento raiz antes da pintura inicial para evitar flashes. A preferência do autor por tons escuros não elimina a versão clara.

## 8. Tipografia e leitura

| Papel | Família | Peso inicial |
| --- | --- | --- |
| Títulos | Manrope | 700–800 |
| Corpo | Source Sans 3 | 400 |
| Ênfase, interface | Source Sans 3 | 600 |
| Código, rótulos técnicos | IBM Plex Mono | 400–500 |

Carregar somente os pesos usados, com suporte a português e `font-display: swap`. Preservar licenças ao distribuir arquivos de fonte. As fontes escritas no painel não garantem que todos os caracteres da imagem correspondam exatamente às famílias reais.

Padrões iniciais de interface:

- Texto de artigo: 18 px no mobile e até 20 px em telas maiores; entrelinha 1,6–1,7.
- Coluna de leitura: aproximadamente 60–70 caracteres, com limite inicial de 68ch.
- Título de artigo: `clamp(2rem, 5vw, 3.5rem)`, entrelinha 1,1–1,2.
- Metadados: 14–16 px, sem perda de contraste.
- Código: 14–16 px, entrelinha próxima de 1,6.
- Texto alinhado à esquerda, sem justificação total.
- Evitar blocos longos em caixa alta ou fonte monoespaçada.
- Notas manuscritas são decoração pontual em capas, nunca fonte de leitura de artigos ou controles.

## 9. Composição e elementos gráficos

- Espaço em branco é parte da identidade nos dois temas.
- Usar escala inicial de espaçamento: 4, 8, 12, 16, 24, 32, 48 e 64 px.
- Margens laterais iniciais: 20 px no mobile e 32 px no desktop.
- Usar separadores finos, margens de caderno e sublinhados manuais com moderação.
- Limitar cada composição a um gesto decorativo dominante.
- Textura de papel discreta em capas e peças sociais; área de leitura lisa.
- Ícones de traço simples e espessura consistente, sem misturar famílias visuais.
- Formas abstratas e recortes de papel podem ilustrar capas quando não houver imagem editorial relevante.
- Evitar neon, vidro translúcido, metal, efeitos holográficos, sombras profundas e decoração tecnológica genérica.
- Não reproduzir textos distorcidos, símbolos acidentais ou outros artefatos do PNG.

## 10. Componentes e estados

### Cabeçalho

Logotipo horizontal, navegação curta e seletor de tema. Em telas pequenas, reduzir a navegação sem comprimir o nome até ficar ilegível. Monograma pode ser usado quando necessário.

### Cards de artigo

Hierarquia: categoria, título, resumo real opcional e metadados reais. Usar espaço e tipografia para organizar o conteúdo. Imagens não são obrigatórias. Não inventar datas, número de leitores, tempo de leitura ou textos autobiográficos para preencher o layout.

### Links e botões

- Links no corpo: cor `--brand` e sublinhado visível.
- Hover de link: reforçar o sublinhado, sem depender apenas de mudança de cor.
- Botão principal: `--brand` com texto `--on-brand`.
- Botão secundário: fundo transparente, texto `--text` e borda de controle.
- Hover de botão secundário: fundo `--surface-raised`.
- Foco por teclado: contorno de 2 px em `--focus`, afastamento de 3 px; verificar contraste no contexto real.
- Não transformar todo texto terracota em chamada para ação.

### Categorias

Rótulos curtos: Bastidores, Engenharia, Carreira, Reflexões e Aprendizados. O texto da categoria é obrigatório; a cor é apenas apoio. Categorias não precisam ter todas as cores da paleta em uma mesma tela.

### Código e citações

Blocos de código em fonte monoespaçada, fundo separado do artigo e rolagem horizontal local quando necessário. Adaptar realce de sintaxe aos dois temas e verificar legibilidade. Citações podem usar uma linha lateral e espaço, sem decoração excessiva.

### Mensagens de sistema

Erros, avisos e sucesso devem ter texto explícito e, quando útil, ícone. As cores de marca não constituem automaticamente uma paleta semântica de estados. Definir e testar cores semânticas adicionais caso a aplicação precise delas.

## 11. Responsividade e acessibilidade

- Projetar a partir de uma coluna no mobile; adicionar colunas somente quando houver espaço para leitura confortável.
- Evitar rolagem horizontal da página. Tabelas e código podem ter contêineres próprios de rolagem.
- Testar a interface a partir de 320 px de largura e com zoom de 200%.
- Objetivo de contraste: pelo menos 4,5:1 para texto comum; 3:1 para texto grande e elementos essenciais de interface, conforme o caso.
- Verificar combinações efetivamente usadas, incluindo hover, foco, imagens e fundos coloridos. Este documento não certifica a acessibilidade de uma implementação.
- Não usar cor como único indicador de estado ou categoria.
- Manter navegação por teclado, foco visível, rótulos acessíveis e hierarquia semântica de títulos.
- Preferir áreas de toque de pelo menos 44 × 44 px.
- Respeitar `prefers-reduced-motion`; transições, quando necessárias, devem ser breves e discretas.
- Elementos decorativos devem ser ignorados por leitores de tela. Conteúdo informativo requer descrição adequada.

## 12. Aplicações

| Peça | Direção |
| --- | --- |
| Blog | Leitura prioritária, fundo liso, título forte e navegação simples |
| Capa de artigo | Categoria, título e um elemento gráfico dominante |
| Post social | Uma ideia central, marca discreta e um destaque terracota |
| Carrossel | Capa direta, páginas internas limpas e numeração consistente |
| Perfil e favicon | Monograma `pj_`, sem avatar |
| Apresentação | Mesmas fontes e paleta, com pouco texto por composição |

Medidas de partida sugeridas: capa de compartilhamento 1200 × 630 px; post quadrado 1080 × 1080 px; post vertical 1080 × 1350 px; story 1080 × 1920 px. São tamanhos de produção iniciais, não garantia de compatibilidade permanente com plataformas externas. Ajustar áreas seguras ao canal de destino.

Os títulos demonstrativos do painel, como “O que eu faria diferente” e “Funcionou. Mas por quê?”, ilustram a composição. Não são artigos escritos ou aprovados pelo autor.

## 13. Orientação para assistentes e LLMs

1. Ler o [guia editorial](../EDITORIAL.md) antes de produzir ou revisar conteúdo editorial.
2. Usar este arquivo como fonte dos valores exatos de identidade.
3. Consultar o PNG para compreender a atmosfera, sem reproduzir o painel como página do site.
4. Construir componentes reais e responsivos; não usar a imagem do painel como interface.
5. Implementar logotipo e monograma em texto ou SVG, respeitando as fontes e as variações.
6. Não reintroduzir avatares, rostos ilustrados ou mascotes.
7. Manter os dois temas completos e coerentes.
8. Priorizar legibilidade sobre textura e ornamentação.
9. Não inventar histórias, credenciais, métricas, depoimentos ou opiniões atribuídas ao autor.
10. Não criar newsletter, comunidade, produtos ou outras funcionalidades apenas porque apareceram em explorações anteriores; implementar o escopo solicitado.
11. Diferenciar regras de marca de medidas iniciais de implementação. Ajustar medidas por necessidade real, preservando a identidade.
12. Informar limitações de assets ou fontes, em vez de afirmar fidelidade exata sem verificação.

## 14. Checklist de entrega

- [ ] Nome, assinatura e underscore corretos.
- [ ] Nenhum avatar ou mascote reutilizado.
- [ ] Paletas implementadas com tokens semânticos.
- [ ] Fontes reais carregadas ou fallback explicitamente informado.
- [ ] Tema claro e escuro completos.
- [ ] Marca legível em tamanho pequeno.
- [ ] Leitura confortável no mobile e desktop.
- [ ] Contraste, teclado, foco e zoom verificados na interface real.
- [ ] Texturas fora das áreas principais de leitura.
- [ ] Nenhum dado editorial inventado para preencher a interface.
- [ ] Logotipo independente do PNG do painel.
- [ ] Aparência humana, editorial, sóbria e acessível.

**Critério final:** a identidade deve apoiar o que Junior tem a compartilhar e tornar a leitura convidativa, preservando a personalidade do projeto.
