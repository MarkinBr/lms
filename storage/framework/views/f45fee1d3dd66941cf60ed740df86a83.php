<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('header_title', 'Dashboard'); ?>
<?php $__env->startSection('header_subtitle', 'Bem-vindo ao seu portal acadêmico'); ?>

<?php $__env->startSection('content'); ?>

    <div class="space-y-6">
        
        <div class="bg-gradient-to-r from-[#003366] via-[#004488] to-[#003366] rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>
            
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2">Olá, <?php echo e(Auth::user()->name ?? 'Estudante'); ?></h1>
                    <p class="text-blue-100 text-lg">Continue sua jornada acadêmica</p>
                </div>
                <div class="hidden lg:flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-blue-200">Semestre Atual</p>
                        <p class="text-2xl font-bold"><?php echo e(date('Y')); ?>.<?php echo e(ceil(date('n')/6)); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:border-blue-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-50 to-transparent rounded-bl-full opacity-50"></div>
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-book-open text-white text-xl"></i>
                        </div>
                        <span class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full">Ativo</span>
                    </div>
                    <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo e($cursosAtivos); ?></p>
                    <p class="text-sm font-semibold text-slate-500">Cursos Ativos</p>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center text-xs text-slate-400">
                            <i class="fa-solid fa-arrow-trend-up mr-2"></i>
                            <span>Em progresso</span>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:border-green-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-green-50 to-transparent rounded-bl-full opacity-50"></div>
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/30 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-chart-line text-white text-xl"></i>
                        </div>
                        <span class="px-3 py-1 bg-green-50 text-green-700 text-xs font-bold rounded-full">
                            <?php echo e($progressoMedio >= 70 ? 'Excelente' : ($progressoMedio >= 50 ? 'Bom' : 'Regular')); ?>

                        </span>
                    </div>
                    <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo e($progressoMedio); ?>%</p>
                    <p class="text-sm font-semibold text-slate-500">Progresso Médio</p>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full transition-all duration-500" style="width: <?php echo e($progressoMedio); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:border-purple-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-50 to-transparent rounded-bl-full opacity-50"></div>
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/30 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-calendar-days text-white text-xl"></i>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($proximasAulas > 5): ?>
                            <span class="px-3 py-1 bg-orange-50 text-orange-700 text-xs font-bold rounded-full">Atenção</span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-purple-50 text-purple-700 text-xs font-bold rounded-full">Normal</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <p class="text-3xl font-bold text-slate-800 mb-1"><?php echo e($proximasAulas); ?></p>
                    <p class="text-sm font-semibold text-slate-500">Aulas Pendentes</p>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center text-xs text-slate-400">
                            <i class="fa-solid fa-clock mr-2"></i>
                            <span>Próximos 7 dias</span>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="group bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-xl hover:border-<?php echo e($statusFinanceiro === 'Em dia' ? 'teal' : 'red'); ?>-200 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-<?php echo e($statusFinanceiro === 'Em dia' ? 'teal' : 'red'); ?>-50 to-transparent rounded-bl-full opacity-50"></div>
                <div class="relative z-10">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-<?php echo e($statusFinanceiro === 'Em dia' ? 'teal' : 'red'); ?>-500 to-<?php echo e($statusFinanceiro === 'Em dia' ? 'teal' : 'red'); ?>-600 rounded-2xl flex items-center justify-center shadow-lg shadow-<?php echo e($statusFinanceiro === 'Em dia' ? 'teal' : 'red'); ?>-500/30 group-hover:scale-110 transition-transform">
                            <i class="fa-solid fa-wallet text-white text-xl"></i>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($statusFinanceiro === 'Em dia'): ?>
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <?php else: ?>
                            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 mb-1"><?php echo e($statusFinanceiro); ?></p>
                    <p class="text-sm font-semibold text-slate-500">Status Financeiro</p>
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center text-xs text-<?php echo e($statusFinanceiro === 'Em dia' ? 'green' : 'red'); ?>-600 font-semibold">
                            <i class="fa-solid fa-<?php echo e($statusFinanceiro === 'Em dia' ? 'circle-check' : 'circle-exclamation'); ?> mr-2"></i>
                            <span><?php echo e($statusFinanceiro === 'Em dia' ? 'Sem pendências' : 'Ação necessária'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 flex items-center">
                            <span class="w-1 h-8 bg-gradient-to-b from-[#003366] to-[#004488] rounded-full mr-3"></span>
                            Meus Cursos
                        </h2>
                        <p class="text-sm text-slate-500 ml-4 mt-1">Continue de onde parou</p>
                    </div>
                    <a href="<?php echo e(route('aluno.cursos')); ?>" class="group flex items-center space-x-2 px-5 py-2.5 bg-[#003366] text-white rounded-xl text-sm font-semibold hover:bg-[#002244] transition-all shadow-sm hover:shadow-lg">
                        <span>Ver Todos</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $cursos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curso): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php if (isset($component)) { $__componentOriginald62bc617157b3da74386ddd7a23ef918 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald62bc617157b3da74386ddd7a23ef918 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.course-card-desktop','data' => ['course' => $curso]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('course-card-desktop'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['course' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($curso)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald62bc617157b3da74386ddd7a23ef918)): ?>
<?php $attributes = $__attributesOriginald62bc617157b3da74386ddd7a23ef918; ?>
<?php unset($__attributesOriginald62bc617157b3da74386ddd7a23ef918); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald62bc617157b3da74386ddd7a23ef918)): ?>
<?php $component = $__componentOriginald62bc617157b3da74386ddd7a23ef918; ?>
<?php unset($__componentOriginald62bc617157b3da74386ddd7a23ef918); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="col-span-2 bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-12 text-center border border-dashed border-slate-300">
                            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                                <i class="fa-solid fa-book-open text-slate-400 text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-600 mb-2">Nenhum curso ativo</h3>
                            <p class="text-sm text-slate-500 mb-6">Você ainda não está matriculado em nenhum curso.</p>
                            <a href="#" class="inline-flex items-center space-x-2 px-6 py-3 bg-[#003366] text-white rounded-xl text-sm font-semibold hover:bg-[#002244] transition-colors">
                                <i class="fa-solid fa-plus"></i>
                                <span>Explorar Cursos</span>
                            </a>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            
            
            <div class="space-y-6">
                
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-50 to-white px-6 py-4 border-b border-slate-200">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <i class="fa-solid fa-bolt text-amber-500 mr-2"></i>
                            Atalhos Rápidos
                        </h3>
                    </div>
                    <div class="p-2">
                        <a href="<?php echo e(route('aluno.calendario')); ?>" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-blue-50 transition-all group">
                            <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-lg group-hover:scale-110 transition-all">
                                <i class="fa-solid fa-calendar-days text-white"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">Calendário</p>
                                <p class="text-xs text-slate-500">Próximas atividades</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"></i>
                        </a>
                        
                        <a href="<?php echo e(route('aluno.financeiro')); ?>" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-orange-50 transition-all group">
                            <div class="w-11 h-11 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-lg group-hover:scale-110 transition-all">
                                <i class="fa-solid fa-wallet text-white"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">Financeiro</p>
                                <p class="text-xs text-slate-500">Boletos e PIX</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-orange-500 group-hover:translate-x-1 transition-all"></i>
                        </a>
                        
                        <a href="<?php echo e(route('aluno.notas')); ?>" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-purple-50 transition-all group">
                            <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-lg group-hover:scale-110 transition-all">
                                <i class="fa-solid fa-star text-white"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">Minhas Notas</p>
                                <p class="text-xs text-slate-500">Boletim acadêmico</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-purple-500 group-hover:translate-x-1 transition-all"></i>
                        </a>
                        
                        <a href="<?php echo e(route('aluno.certificados')); ?>" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-green-50 transition-all group">
                            <div class="w-11 h-11 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-sm group-hover:shadow-lg group-hover:scale-110 transition-all">
                                <i class="fa-solid fa-certificate text-white"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">Certificados</p>
                                <p class="text-xs text-slate-500">Meus documentos</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-green-500 group-hover:translate-x-1 transition-all"></i>
                        </a>
                    </div>
                </div>
                
                
                <div class="bg-white rounded-2xl shadow-sm border border-amber-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 px-6 py-4 border-b border-amber-200">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-500 rounded-lg flex items-center justify-center shadow-sm">
                                <i class="fa-solid fa-bullhorn text-white text-sm"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">Mural de Avisos</h3>
                        </div>
                    </div>
                    
                    <div class="p-4 space-y-3 max-h-80 overflow-y-auto">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $avisos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $aviso): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="bg-gradient-to-br from-white to-amber-50/30 p-4 rounded-xl border border-amber-100 hover:shadow-md transition-all">
                                <div class="flex items-start space-x-3">
                                    <div class="w-2 h-2 bg-amber-500 rounded-full mt-2 flex-shrink-0"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-800 mb-1"><?php echo e($aviso->titulo); ?></p>
                                        <p class="text-xs text-slate-600 leading-relaxed"><?php echo e(Str::limit($aviso->mensagem, 80)); ?></p>
                                        <p class="text-[10px] text-slate-400 mt-2"><?php echo e($aviso->created_at->diffForHumans()); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="p-8 text-center">
                                <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fa-solid fa-bell-slash text-amber-300 text-2xl"></i>
                                </div>
                                <p class="text-xs text-slate-500">Nenhum aviso no momento</p>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                
                
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-50 to-white px-6 py-4 border-b border-slate-200">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <i class="fa-solid fa-chart-pie text-blue-500 mr-2"></i>
                            Meu Progresso Global
                        </h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-600">Aulas Assistidas</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Total do semestre</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-slate-800"><?php echo e($totalAssistidasGeral); ?></p>
                                    <p class="text-[10px] text-slate-400">de <?php echo e($totalAulasGeral); ?></p>
                                </div>
                            </div>
                            <div class="relative h-3 bg-slate-100 rounded-full overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-blue-600 to-blue-500 rounded-full transition-all duration-700 animate-pulse" style="width: <?php echo e($percentualGeral); ?>%; opacity: 0.2;"></div>
                                <div class="relative h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full shadow-sm transition-all duration-700" style="width: <?php echo e($percentualGeral); ?>%"></div>
                            </div>
                            <p class="text-xs text-slate-500 mt-2 text-center"><?php echo e($percentualGeral); ?>% concluído</p>
                        </div>
                        
                        <div class="pt-5 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-600">Frequência</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Estimativa atual</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600">100%</p>
                                    <p class="text-[10px] text-green-500">Excelente</p>
                                </div>
                            </div>
                            <div class="relative h-3 bg-slate-100 rounded-full overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-r from-green-500 via-green-600 to-green-500 rounded-full transition-all duration-700 animate-pulse" style="width: 100%; opacity: 0.2;"></div>
                                <div class="relative h-full bg-gradient-to-r from-green-500 to-green-600 rounded-full shadow-sm transition-all duration-700" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <div class="pt-5 border-t border-slate-100">
                            <a href="<?php echo e(route('aluno.notas')); ?>" class="flex items-center justify-between p-3 rounded-xl bg-gradient-to-r from-slate-50 to-blue-50 hover:from-blue-50 hover:to-blue-100 transition-all group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-sm">
                                        <i class="fa-solid fa-trophy text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800">Ver Desempenho</p>
                                        <p class="text-[10px] text-slate-500">Análise completa</p>
                                    </div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-slate-400 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-8">
            <div class="bg-gradient-to-br from-blue-50 to-white p-6 rounded-2xl border border-blue-100">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-headset text-white"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800">Suporte Acadêmico</h4>
                </div>
                <p class="text-xs text-slate-600 mb-3">Precisa de ajuda? Nossa equipe está pronta para auxiliar.</p>
                <a href="#" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center">
                    <span>Abrir chamado</span>
                    <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
                </a>
            </div>
            
            <div class="bg-gradient-to-br from-purple-50 to-white p-6 rounded-2xl border border-purple-100">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-purple-500 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-book text-white"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800">Biblioteca Virtual</h4>
                </div>
                <p class="text-xs text-slate-600 mb-3">Acesse milhares de livros e artigos acadêmicos.</p>
                <a href="#" class="text-xs font-semibold text-purple-600 hover:text-purple-700 flex items-center">
                    <span>Explorar acervo</span>
                    <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
                </a>
            </div>
            
            <div class="bg-gradient-to-br from-green-50 to-white p-6 rounded-2xl border border-green-100">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-users text-white"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800">Comunidade</h4>
                </div>
                <p class="text-xs text-slate-600 mb-3">Conecte-se com outros alunos e professores.</p>
                <a href="#" class="text-xs font-semibold text-green-600 hover:text-green-700 flex items-center">
                    <span>Participar</span>
                    <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
                </a>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.aluno', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Marcos\Downloads\cetsadp-lms-completo\lms\resources\views/aluno/dashboard.blade.php ENDPATH**/ ?>