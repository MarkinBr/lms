@extends('layouts.aluno')

@section('title', $curso->titulo)
@section('header_title', $curso->titulo)
@section('header_subtitle', 'Acompanhe módulos, aulas e progresso do seu curso')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('aluno.cursos') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600">
                        Meus Cursos
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-gray-500 dark:text-gray-400">{{ $curso->titulo }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        @if(($materiaisLegados ?? collect())->isNotEmpty())
            <section class="mb-6 rounded-lg border border-amber-200 bg-white p-5 dark:bg-gray-800" aria-labelledby="materiais-do-curso">
                <h2 id="materiais-do-curso" class="text-lg font-semibold">Materiais do curso</h2>
                <ul class="mt-3 space-y-3">
                    @foreach($materiaisLegados as $materialLegado)
                        <li>
                            <a href="{{ $materialLegado->url }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-2 text-blue-700 underline dark:text-blue-300">
                                {{ $materialLegado->nome }}
                                <span class="text-xs uppercase">{{ $materialLegado->tipo }} · abre em outra aba</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <!-- Header do Curso -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg overflow-hidden mb-8">
            <div class="p-8">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        @if($curso->categoria)
                            <span class="inline-block px-3 py-1 bg-white/20 text-white text-sm font-semibold rounded-full mb-3">
                                {{ $curso->categoria }}
                            </span>
                        @endif

                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                            {{ $curso->titulo }}
                        </h1>

                        @if($curso->descricao)
                            <div class="text-white/90 text-sm md:text-base prose prose-invert max-w-none">
                                {!! $curso->descricao !!}
                            </div>
                        @endif
                    </div>

                    @if($curso->capa)
                        <img src="{{ asset('storage/' . $curso->capa) }}" 
                             alt="{{ $curso->titulo }}"
                             class="hidden md:block w-32 h-32 object-cover rounded-lg shadow-lg ml-6">
                    @endif
                </div>

                <!-- Estatísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 rounded-lg p-4">
                        <p class="text-white/70 text-xs uppercase tracking-wide">Progresso</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $progresso }}%</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4">
                        <p class="text-white/70 text-xs uppercase tracking-wide">Módulos</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $totalModulos }}</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4">
                        <p class="text-white/70 text-xs uppercase tracking-wide">Aulas</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $totalAulas }}</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4">
                        <p class="text-white/70 text-xs uppercase tracking-wide">Concluídas</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $aulasConcluidas }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo do Curso -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Módulos e Aulas (Coluna Principal) -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Conteúdo do Curso
                        </h2>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($modulos as $modulo)
                            <div class="p-6">
                                <!-- Cabeçalho do Módulo -->
                                <button 
                                    type="button"
                                    class="w-full flex items-center justify-between text-left group"
                                    onclick="toggleModulo({{ $modulo->id }})">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                            Módulo {{ $loop->iteration }}: {{ $modulo->titulo }}
                                        </h3>
                                        @if($modulo->descricao)
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $modulo->descricao }}
                                            </p>
                                        @endif
                                        <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $modulo->aulas->count() }} aulas</span>
                                            @php
                                                $aulasConcluidasModulo = $modulo->aulas->filter(function ($aula) {
                                                    return (bool) ($aula->concluida_portal ?? false);
                                                })->count();
                                            @endphp
                                            <span class="mx-2">•</span>
                                            <span>{{ $aulasConcluidasModulo }} concluídas</span>
                                        </div>
                                    </div>

                                    <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200 modulo-icon-{{ $modulo->id }}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Lista de Aulas -->
                                <div id="modulo-{{ $modulo->id }}" class="mt-4 space-y-2 hidden">
                                    @foreach($modulo->aulas as $aula)
                                        @php
                                            $concluida = (bool) ($aula->concluida_portal ?? false);
                                        @endphp

                                        <a href="{{ route('aluno.aula', $aula->id) }}" 
                                           class="flex items-center p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                            <!-- Ícone de Status -->
                                            <div class="flex-shrink-0 mr-4">
                                                @if($concluida)
                                                    <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                @elseif($aula->liberada)
                                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Info da Aula -->
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-blue-600 transition-colors">
                                                    {{ $aula->titulo }}
                                                </p>
                                                <div class="mt-1 flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                    @if($aula->duracao)
                                                        <span>{{ $aula->duracao }}</span>
                                                    @endif
                                                    @if($aula->materiais && count($aula->materiais) > 0)
                                                        <span class="mx-2">•</span>
                                                        <span>{{ count($aula->materiais) }} materiais</span>
                                                    @endif
                                                    @if(!$aula->liberada)
                                                        <span class="mx-2">•</span>
                                                        <span class="text-red-500">Bloqueada</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Seta -->
                                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                    Nenhum módulo disponível
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    O conteúdo do curso ainda não foi adicionado.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Barra de Progresso Detalhada -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Seu Progresso
                    </h3>

                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                    Em andamento
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold inline-block text-blue-600">
                                    {{ $progresso }}%
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                            <div style="width:{{ $progresso }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-600 transition-all duration-500"></div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Total de aulas</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $totalAulas }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Concluídas</span>
                            <span class="font-semibold text-green-600">{{ $aulasConcluidas }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Faltam</span>
                            <span class="font-semibold text-orange-600">{{ $totalAulas - $aulasConcluidas }}</span>
                        </div>
                    </div>
                </div>

                <!-- Informações do Curso -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Informações do Curso
                    </h3>

                    <div class="space-y-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Nível</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $curso->nivel }}</p>
                            </div>
                        </div>

                        @if($curso->duracao_total)
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Duração</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $curso->duracao_total }} minutos</p>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Matrícula</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $matricula->data_matricula->format('d/m/Y') }}</p>
                            </div>
                        </div>

                        @if($matricula->data_validade)
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Validade</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $matricula->data_validade->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleModulo(moduloId) {
    const content = document.getElementById(`modulo-${moduloId}`);
    const icon = document.querySelector(`.modulo-icon-${moduloId}`);
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

// Abrir primeiro módulo por padrão
document.addEventListener('DOMContentLoaded', function() {
    const firstModulo = document.querySelector('[id^="modulo-"]');
    if (firstModulo) {
        const moduloId = firstModulo.id.replace('modulo-', '');
        toggleModulo(moduloId);
    }
});
</script>
@endpush
@endsection
