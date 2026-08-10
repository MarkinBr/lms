<script>
window.CETSADP_CONTENT = {
  texts: {
    "contato.telefone": <?php echo json_encode($dados->telefone ?? '(21) 2683-3271', 15, 512) ?>,
    "contato.telefone_link": <?php echo json_encode(preg_replace('/\D+/', '', $dados->telefone ?? '2126833271')) ?>,
    "contato.email": <?php echo json_encode($dados->email ?? 'contato@cetsadp.com.br', 15, 512) ?>,
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
  alunoLogin: <?php echo json_encode(route('aluno.login'), 15, 512) ?>,
  adminLogin: '/admin/login',
  contato: <?php echo json_encode(route('site.contato'), 15, 512) ?>,
  newsletter: <?php echo json_encode(route('site.newsletter'), 15, 512) ?>,
  home: <?php echo json_encode(url('/'), 15, 512) ?>
};
</script>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
  <script>window.__FLASH_SUCCESS = <?php echo json_encode(session('success'), 15, 512) ?>;</script>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
  <script>window.__FLASH_ERRORS = <?php echo json_encode($errors->all(), 15, 512) ?>;</script>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\Marcos\Downloads\cetsadp-lms-completo\lms\resources\views/site/partials/dynamic-data.blade.php ENDPATH**/ ?>