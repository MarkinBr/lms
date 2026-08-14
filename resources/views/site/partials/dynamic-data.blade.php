<script>
window.CETSADP_CONTENT = {
  texts: {
    "contato.telefone": @json($dados->telefone ?? '(21) 2683-3271'),
    "contato.telefone_link": @json(preg_replace('/\D+/', '', $dados->telefone ?? '2126833271')),
    "contato.email": @json($dados->email ?? 'contato@cetsadp.com.br'),
    "topbar.cidade": "Paracambi - RJ",
    "topbar.validar": "Validar Documento",
    "topbar.portal_aluno": "Portal do Aluno",
    "brand.nome": "CETSADP",
    "brand.tagline": "Centro Educacional, Teológico e Social da Igreja Evangélica Assembleia de Deus em Paracambi",
    "menu.inicio": "INÍCIO",
    "menu.sobre": "SOBRE NÓS",
    "menu.cursos": "CURSOS",
    "menu.professores": "PROFESSORES",
    "menu.eventos": "EVENTOS",
    "menu.noticias": "NOTÍCIAS",
    "menu.contato": "CONTATO",
    "menu.cta": "MATRÍCULAS ABERTAS",
    "teologia.nome": "Curso Médio em Teologia",
    "diaconato.nome": "Habilitação ao Diaconato",
    "lideranca.nome": "Liderança Cristã Ministerial",
    "menu.teologia_sub": "Formação bíblica completa · 2 anos",
    "menu.diaconato_sub": "Sirva com excelência · 7 meses",
    "menu.lideranca_sub": "Forme líderes · 2 anos"
  }
};
window.LARAVEL_ROUTES = {
  alunoLogin: @json(route('aluno.login')),
  adminLogin: '/admin/login',
  contato: @json(route('site.contato')),
  newsletter: @json(route('site.newsletter')),
  home: @json(url('/'))
};
</script>

@if(session('success'))
  <script>window.__FLASH_SUCCESS = @json(session('success'));</script>
@endif
@if(session('error'))
  <script>window.__FLASH_ERROR = @json(session('error'));</script>
@endif
@if($errors->any())
  <script>window.__FLASH_ERRORS = @json($errors->all());</script>
@endif
