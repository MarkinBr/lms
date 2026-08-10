<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<title><?php echo e($dados->hero_titulo_principal ?? 'CETSADP'); ?> — Centro Educacional, Teológico e Social</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;0,900;1,500;1,600&family=Mulish:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo e(asset('css/site/main.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('css/site/hero-cursos.css')); ?>">

<!-- ================= CETSADP CMS BOOTSTRAP ================= -->
<style id="cms-palette-vars">
/* Sobrescritas de paleta injetadas pelo painel */
</style>
<script src="<?php echo e(asset('js/site/cms-bootstrap.js')); ?>"></script>
<script src="/js/cms-bootstrap.js"></script>
<!-- =============== FIM CETSADP CMS BOOTSTRAP =============== -->

</head>
<body>


<?php echo $__env->make('site.partials.dynamic-data', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div id="topbar-root"></div>
<header class="header" id="header-root"></header>
<main class="page-bg">
<div class="dotgrid"></div>


  
  <?php echo $__env->make('site.pages.home', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.teologia', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.diaconato', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.lideranca', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.inscricao', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.sobre', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.professores', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.eventos', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.noticias', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.pages.contato', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


  
  <?php echo $__env->make('site.app.login-secretaria', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.login-aluno', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.dash-secretaria', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.dash-aluno', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-alunos', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-academico', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-financeiro', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-secretaria', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-relatorios', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-documentos', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-certificados', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-mensagens', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('site.app.sec-config', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</main>

<div id="footer-root"></div>
<button class="totop" id="totop" aria-label="Voltar ao topo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg></button>
<div class="toast" id="toast"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg><span id="toastMsg"></span></div>

<script src="<?php echo e(asset('js/site/app.js')); ?>"></script>
<script src="<?php echo e(asset('js/site/hero-cursos.js')); ?>"></script>

</body>
</html>
<?php /**PATH C:\Users\Marcos\Downloads\cetsadp-lms-completo\lms\resources\views/site/index.blade.php ENDPATH**/ ?>