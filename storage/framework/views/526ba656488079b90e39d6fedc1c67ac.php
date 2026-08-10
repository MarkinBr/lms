<?php $__env->startSection('title', 'Meus Cursos'); ?>
<?php $__env->startSection('header_title', 'Meus Cursos'); ?>
<?php $__env->startSection('header_subtitle', 'Continue seus estudos sem erros e com acesso direto às aulas'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-8">
    <section class="bg-gradient-to-r from-[#003366] via-blue-800 to-indigo-700 rounded-3xl p-6 md:p-8 text-white shadow-2xl overflow-hidden relative">
        <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full bg-white/10"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 rounded-full bg-white/5"></div>
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div class="max-w-2xl">
                <p class="text-blue-200 text-xs font-black uppercase tracking-[0.35em] mb-3">Área Acadêmica</p>
                <h1 class="text-3xl md:text-4xl font-black mb-3">Continue firme na sua jornada</h1>
                <p class="text-blue-100 text-sm md:text-base">Seus cursos estão disponíveis para ajudar no seu crescimento espiritual e no conhecimento da Palavra. Acesse e siga avançando.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 w-full lg:w-auto">
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[130px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Total</p>
                    <p class="text-3xl font-black mt-1"><?php echo e($resumo['total_cursos']); ?></p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[130px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Ativos</p>
                    <p class="text-3xl font-black mt-1"><?php echo e($resumo['ativos']); ?></p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[130px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Concluídos</p>
                    <p class="text-3xl font-black mt-1"><?php echo e($resumo['concluidos']); ?></p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl px-4 py-4 min-w-[130px]">
                    <p class="text-[11px] uppercase tracking-widest text-blue-200 font-bold">Média</p>
                    <p class="text-3xl font-black mt-1"><?php echo e($resumo['progresso_medio']); ?>%</p>
                </div>
            </div>
        </div>
    </section>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($proximasAulasAoVivo->isNotEmpty()): ?>
        <section class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
            <div class="flex items-center justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-800">Próximas aulas ao vivo</h2>
                    <p class="text-sm text-slate-500">Eventos próximos vinculados aos seus cursos.</p>
                </div>
                <a href="<?php echo e(route('aluno.calendario')); ?>" class="text-sm font-bold text-blue-700 hover:text-blue-900">Abrir calendário</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $proximasAulasAoVivo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $aula): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <p class="text-xs uppercase tracking-widest text-blue-600 font-black">Aula ao vivo</p>
                                <h3 class="font-bold text-slate-800 leading-tight mt-1"><?php echo e($aula->titulo); ?></h3>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black bg-white text-blue-700 border border-blue-100"><?php echo e(optional($aula->data_hora)->format('d/m')); ?></span>
                        </div>
                        <div class="space-y-2 text-sm text-slate-600">
                            <p><i class="fa-regular fa-clock w-4 mr-1 text-blue-600"></i><?php echo e(optional($aula->data_hora)->format('d/m/Y \à\s H:i')); ?></p>
                            <p><i class="fa-solid fa-book w-4 mr-1 text-blue-600"></i><?php echo e($aula->curso->titulo ?? 'Curso'); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($aula->turma): ?>
                                <p><i class="fa-solid fa-users w-4 mr-1 text-blue-600"></i><?php echo e($aula->turma->nome); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($aula->link_transmissao): ?>
                            <a href="<?php echo e($aula->link_transmissao); ?>" target="_blank" class="mt-4 inline-flex items-center justify-center w-full rounded-xl bg-[#003366] text-white px-4 py-2.5 text-sm font-bold hover:bg-[#00264d] transition-colors">
                                Entrar na sala
                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($matriculas->count()): ?>
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $matriculas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $matricula): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $curso = $matricula->curso;
                    $progresso = $matricula->progresso ?? 0;
                    $status = strtolower((string) $matricula->status);
                    $statusLabel = $status === 'concluido' ? 'Concluído' : 'Em andamento';
                ?>
                <article class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden hover:shadow-xl hover:border-blue-200 transition-all duration-300">
                    <div class="p-6 md:p-7">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide <?php echo e($status === 'concluido' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'); ?>">
                                        <?php echo e($statusLabel); ?>

                                    </span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($curso?->categoria): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600"><?php echo e($curso->categoria); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <h2 class="text-2xl font-black text-slate-800 leading-tight"><?php echo e($curso->titulo ?? 'Curso sem título'); ?></h2>
                                <p class="text-sm text-slate-500 mt-2"><?php echo e(\Illuminate\Support\Str::limit(strip_tags($curso->descricao ?? 'Curso vinculado à sua matrícula.'), 130)); ?></p>
                            </div>
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center shrink-0 overflow-hidden">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($curso?->capa)): ?>
                                    <img src="<?php echo e(asset('storage/' . $curso->capa)); ?>" alt="<?php echo e($curso->titulo); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fa-solid fa-graduation-cap text-2xl text-[#003366]"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black uppercase tracking-widest text-slate-500">Progresso</span>
                                <span class="text-sm font-black text-blue-700"><?php echo e($progresso); ?>%</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-3 rounded-full bg-gradient-to-r <?php echo e($progresso === 100 ? 'from-green-500 to-emerald-500' : 'from-blue-600 to-indigo-600'); ?>" style="width: <?php echo e($progresso); ?>%"></div>
                            </div>
                            <p class="text-xs text-slate-500 mt-2"><?php echo e($matricula->aulas_concluidas ?? 0); ?> de <?php echo e($matricula->total_aulas ?? 0); ?> aulas concluídas.</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mt-5">
                            <div class="rounded-2xl bg-slate-50 p-4 text-center">
                                <p class="text-xs uppercase tracking-widest text-slate-400 font-black">Módulos</p>
                                <p class="text-2xl font-black text-slate-800 mt-1"><?php echo e($curso?->modulos?->count() ?? 0); ?></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 text-center">
                                <p class="text-xs uppercase tracking-widest text-slate-400 font-black">Aulas</p>
                                <p class="text-2xl font-black text-slate-800 mt-1"><?php echo e($matricula->total_aulas ?? 0); ?></p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 text-center">
                                <p class="text-xs uppercase tracking-widest text-slate-400 font-black">Carga</p>
                                <p class="text-2xl font-black text-slate-800 mt-1"><?php echo e(max(1, (int) ceil(($matricula->tempo_total_minutos ?? 0) / 60))); ?>h</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col sm:flex-row gap-3">
                            <a href="<?php echo e(route('aluno.curso.show', $curso->id)); ?>" class="inline-flex items-center justify-center rounded-2xl bg-[#003366] text-white px-5 py-3 text-sm font-black hover:bg-[#00264d] transition-colors flex-1">
                                <i class="fa-solid fa-arrow-right mr-2"></i>
                                <?php echo e($progresso > 0 ? 'Continuar curso' : 'Acessar curso'); ?>

                            </a>
                            <a href="<?php echo e(route('aluno.calendario')); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 text-slate-700 px-5 py-3 text-sm font-bold hover:border-blue-200 hover:text-blue-700 transition-colors">
                                <i class="fa-solid fa-calendar-days mr-2"></i>
                                Ver agenda
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </section>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($matriculas->hasPages()): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <?php echo e($matriculas->links()); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php else: ?>
        <section class="bg-white border border-dashed border-slate-300 rounded-3xl p-12 text-center shadow-sm">
            <div class="w-20 h-20 rounded-full bg-slate-100 mx-auto flex items-center justify-center mb-5">
                <i class="fa-solid fa-book-open text-3xl text-slate-400"></i>
            </div>
            <h2 class="text-2xl font-black text-slate-800">Nenhum curso encontrado</h2>
            <p class="text-slate-500 mt-2 max-w-xl mx-auto">Quando sua matrícula estiver ativa, seus cursos aparecerão aqui com acesso direto ao conteúdo, progresso e calendário acadêmico.</p>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.aluno', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Marcos\Downloads\cetsadp-lms-completo\lms\resources\views/aluno/cursos/index.blade.php ENDPATH**/ ?>