<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $dados->hero_titulo_principal ?? 'CETSADP' }} — Centro Educacional, Teológico e Social</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;0,900;1,500;1,600&family=Mulish:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site/main.css') }}">
<link rel="stylesheet" href="{{ asset('css/site/hero-cursos.css') }}">

<!-- ================= CETSADP CMS BOOTSTRAP ================= -->
<style id="cms-palette-vars">
/* Sobrescritas de paleta injetadas pelo painel */
</style>
<script src="{{ asset('js/site/cms-bootstrap.js') }}"></script>
<script src="/js/cms-bootstrap.js"></script>
<!-- =============== FIM CETSADP CMS BOOTSTRAP =============== -->

</head>
<body>

{{-- Dados dinâmicos (CMS content, rotas Laravel, mensagens de flash session) --}}
@include('site.partials.dynamic-data')

<div id="topbar-root"></div>
<header class="header" id="header-root"></header>
<main class="page-bg">
<div class="dotgrid"></div>

{{-- ============================================================
     PÁGINAS PÚBLICAS DO SITE
============================================================ --}}
  {{-- Início (data-page="home") --}}
  @include('site.pages.home')

  {{-- Curso: Médio em Teologia (data-page="teologia") --}}
  @include('site.pages.teologia')

  {{-- Curso: Habilitação ao Diaconato (data-page="diaconato") --}}
  @include('site.pages.diaconato')

  {{-- Curso: Liderança Cristã Ministerial (data-page="lideranca") --}}
  @include('site.pages.lideranca')

  {{-- Inscrição / Matrícula (data-page="inscricao") --}}
  @include('site.pages.inscricao')

  {{-- Acompanhar Inscrição (data-page="acompanhar-inscricao") --}}
  @include('site.pages.acompanhar-inscricao')

  {{-- Termos e Condições (data-page="termos-e-condicoes") --}}
  @include('site.pages.termos-e-condicoes')

  {{-- Política de Privacidade (data-page="politica-de-privacidade") --}}
  @include('site.pages.politica-de-privacidade')

  {{-- Sobre Nós (data-page="sobre") --}}
  @include('site.pages.sobre')

  {{-- Professores (data-page="professores") --}}
  @include('site.pages.professores')

  {{-- Eventos (data-page="eventos") --}}
  @include('site.pages.eventos')

  {{-- Notícias (data-page="noticias") --}}
  @include('site.pages.noticias')

  {{-- Contato (data-page="contato") --}}
  @include('site.pages.contato')

{{-- ============================================================
     PAINÉIS INTERNOS (SECRETARIA / ALUNO) — protótipo de interface
============================================================ --}}
  {{-- Login da secretaria (data-page="login-secretaria") --}}
  @include('site.app.login-secretaria')

  {{-- Login do aluno (data-page="login-aluno") --}}
  @include('site.app.login-aluno')

  {{-- Dashboard da secretaria (data-page="dash-secretaria") --}}
  @include('site.app.dash-secretaria')

  {{-- Dashboard do aluno (data-page="dash-aluno") --}}
  @include('site.app.dash-aluno')

  {{-- Secretaria: Alunos (data-page="sec-alunos") --}}
  @include('site.app.sec-alunos')

  {{-- Secretaria: Acadêmico (data-page="sec-academico") --}}
  @include('site.app.sec-academico')

  {{-- Secretaria: Financeiro (data-page="sec-financeiro") --}}
  @include('site.app.sec-financeiro')

  {{-- Secretaria: Secretaria (data-page="sec-secretaria") --}}
  @include('site.app.sec-secretaria')

  {{-- Secretaria: Relatórios (data-page="sec-relatorios") --}}
  @include('site.app.sec-relatorios')

  {{-- Secretaria: Documentos (data-page="sec-documentos") --}}
  @include('site.app.sec-documentos')

  {{-- Secretaria: Certificados (data-page="sec-certificados") --}}
  @include('site.app.sec-certificados')

  {{-- Secretaria: Mensagens (data-page="sec-mensagens") --}}
  @include('site.app.sec-mensagens')

  {{-- Secretaria: Configurações (data-page="sec-config") --}}
  @include('site.app.sec-config')

</main>

<div id="footer-root"></div>
<button class="totop" id="totop" aria-label="Voltar ao topo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg></button>
<div class="toast" id="toast"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg><span id="toastMsg"></span></div>

<script src="{{ asset('js/site/app.js') }}"></script>
<script src="{{ asset('js/site/hero-cursos.js') }}"></script>

</body>
</html>
