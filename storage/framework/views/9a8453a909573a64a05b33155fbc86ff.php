<?php
    $userAgent = strtolower(request()->userAgent() ?? '');
    $isMobile = (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent);
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isMobile): ?>
    <?php echo $__env->make('layouts.aluno-mobile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php else: ?>
    <?php echo $__env->make('layouts.aluno-desktop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\Users\Marcos\Downloads\cetsadp-lms-completo\lms\resources\views/layouts/aluno.blade.php ENDPATH**/ ?>