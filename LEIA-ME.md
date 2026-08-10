# CETSADP — index.blade.php dividido em múltiplos arquivos

Este pacote reorganiza o `index_blade.php` original (2.596 linhas / ~390 KB) em 31
arquivos menores, sem alterar nenhum comportamento. Todo o conteúdo foi extraído
linha a linha do arquivo original (com verificação byte-a-byte) — nada foi
reescrito ou "melhorado" no processo, só reorganizado.

## Por que esses cortes

O arquivo já tinha divisões naturais bem marcadas:
- Um bloco `<style>` gigante (CSS do site inteiro) envolto em `@verbatim`.
- Um bloco `<script>` gigante (ícones, roteador de "páginas", montagem do
  header/footer, formulários) também em `@verbatim`.
- 23 blocos `<div class="page" data-page="...">`, um por "página" do site
  (que na verdade é uma SPA client-side: todas as páginas ficam no DOM e o
  JS mostra/esconde por `data-page`).
- Um widget isolado (carrossel de cursos no topo da Home) com seu próprio
  CSS e JS, já demarcado por comentários no arquivo original.

Segui exatamente essas costuras — nenhum corte foi "inventado".

Confirmei antes de dividir que **CSS e JS não têm nenhuma sintaxe Blade**
(nenhum `{{ }}`, `@if`, `@foreach` etc. dentro deles), então puderam virar
arquivos estáticos `.css`/`.js` de verdade — o que também elimina a
necessidade do `@verbatim`. O conteúdo dinâmico (Blade) fica concentrado em
só 2 lugares: o `partials/dynamic-data.blade.php` (dados do CMS, rotas,
mensagens de sessão) e 5 formulários que usam `route()`/`@csrf` dentro das
suas respectivas páginas.

## Estrutura

```
resources/views/site/
  index.blade.php              ← view principal (era o arquivo original)
  partials/
    dynamic-data.blade.php     ← window.CETSADP_CONTENT, LARAVEL_ROUTES, flash messages
    hero-cursos.blade.php      ← HTML do carrossel de cursos (usado dentro de home)
  pages/                       ← páginas públicas do site
    home.blade.php
    teologia.blade.php
    diaconato.blade.php
    lideranca.blade.php
    inscricao.blade.php
    sobre.blade.php
    professores.blade.php
    eventos.blade.php
    noticias.blade.php
    contato.blade.php
  app/                         ← protótipo de painel (login/dashboard secretaria e aluno)
    login-secretaria.blade.php
    login-aluno.blade.php
    dash-secretaria.blade.php
    dash-aluno.blade.php
    sec-alunos.blade.php
    sec-academico.blade.php
    sec-financeiro.blade.php
    sec-secretaria.blade.php
    sec-relatorios.blade.php
    sec-documentos.blade.php
    sec-certificados.blade.php
    sec-mensagens.blade.php
    sec-config.blade.php

public/css/site/
  main.css                     ← CSS principal do site (era o <style> de ~1180 linhas)
  hero-cursos.css               ← CSS específico do carrossel de cursos

public/js/site/
  cms-bootstrap.js              ← lê localStorage/CMS e aplica paleta de cores
  app.js                        ← ícones, roteador (função go()), header/footer, formulários, widgets
  hero-cursos.js                 ← lógica do carrossel de cursos
```

## Como integrar no seu projeto

1. Copie a pasta `resources/views/site/` para dentro do seu `resources/views/`
   (renomeie se já existir algo com esse nome).
2. Copie `public/css/site/` e `public/js/site/` para dentro do seu `public/`.
   Como estão em `public/`, `asset('css/site/main.css')` e
   `asset('js/site/app.js')` funcionam direto, sem build step (Vite/Mix) —
   igual ao `<style>`/`<script>` inline de antes.
3. Aponte sua rota/controller para `site.index` em vez do view antigo, ex.:
   ```php
   return view('site.index', ['dados' => $dados]);
   ```
4. Se seu `resources/views` **não** tiver uma pasta `site/` (ex.: você prefere
   colocar tudo direto em `resources/views/`), ajuste os `@include('site.pages.home')`
   etc. dentro de `index.blade.php` removendo o prefixo `site.` — é só isso que
   precisa mudar.

Nada mais precisa de ajuste: os 5 formulários que usam `route()`/`@csrf`
(inscrição, newsletter, contato, login secretaria, login aluno) continuam
funcionando normalmente dentro dos seus respectivos arquivos, porque o Blade
processa `@include` normalmente (não é um "iframe" nem perde acesso a
`route()`, `$dados`, sessão etc.).

## Adicionando uma página nova no futuro

Basta criar `resources/views/site/pages/nova-pagina.blade.php` com
`<div class="page" data-page="nova-pagina">...</div>` e adicionar
`@include('site.pages.nova-pagina')` em `index.blade.php`, no mesmo padrão
das outras.

## Duas observações (não mudei nada por conta delas, só percebi ao revisar)

1. **`</head>` duplicado**: o arquivo original tinha um `</head>` sobrando logo
   antes do `<body>` (um dentro do bloco `@verbatim` do CMS bootstrap e outro
   logo depois). É inofensivo — o navegador ignora tag de fechamento
   redundante — mas no `index.blade.php` novo deixei só um, já que estava
   remontando essa parte de qualquer forma.
2. **Formulários em modo demonstração**: `inscForm`, `loginSecForm`,
   `loginAluForm`, `nlForm` e `contactForm` têm `e.preventDefault()` no
   `app.js` e só mostram um toast — ou seja, hoje eles **não chegam a enviar**
   para as rotas Laravel configuradas no `action`. Isso já era assim no
   arquivo original (não mudei nada), só deixando registrado caso você não
   tivesse notado — quando for ligar esses formulários ao backend de verdade,
   é essa parte do `app.js` que precisa mudar.
