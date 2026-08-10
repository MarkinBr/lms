{{-- 
    CETSADP — Dashboard do Aluno (Blade Template)
    
    Variáveis:
      $curso     — array de configuração visual (do middleware)
      $aluno     — model do aluno autenticado
      $aulas     — collection de aulas do curso
      $aulaLive  — aula ao vivo atual (nullable)
      $progresso — percentual de conclusão
      $avisos    — últimos avisos do professor
      $materiais — materiais disponíveis
--}}
@extends('layouts.portal')

@section('title', 'Dashboard — ' . $curso['nome'])

@section('content')
<div class="min-h-screen bg-gray-50">

    {{-- ═══════════════════════════════════════════ --}}
    {{-- TOP BAR                                     --}}
    {{-- ═══════════════════════════════════════════ --}}
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo + Curso --}}
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                         style="background: {{ $curso['cor_primaria'] }}">
                        <span class="text-white font-heading font-bold text-xs">C</span>
                    </div>
                    <div class="hidden sm:block">
                        <span class="font-heading text-sm font-semibold" style="color: {{ $curso['cor_primaria'] }}">CETSADP</span>
                        <span class="mx-2 text-gray-300">|</span>
                        <span class="text-sm text-gray-600">{{ $curso['nome_curto'] }}</span>
                    </div>
                </div>

                {{-- Nav links --}}
                <nav class="hidden md:flex items-center gap-1">
                    <a href="{{ route('curso.dashboard', ['curso' => $curso['id']]) }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                       style="background: {{ $curso['cor_primaria'] }}10; color: {{ $curso['cor_primaria'] }}">
                        <i class="fas fa-home mr-1"></i> Início
                    </a>
                    <a href="{{ route('curso.aulas', ['curso' => $curso['id']]) }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-play-circle mr-1"></i> Aulas
                    </a>
                    <a href="{{ route('curso.materiais', ['curso' => $curso['id']]) }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-file-pdf mr-1"></i> Materiais
                    </a>
                    <a href="{{ route('curso.notas', ['curso' => $curso['id']]) }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-chart-bar mr-1"></i> Notas
                    </a>
                    <a href="{{ route('curso.financeiro', ['curso' => $curso['id']]) }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-wallet mr-1"></i> Financeiro
                    </a>
                </nav>

                {{-- User area --}}
                <div class="flex items-center gap-3">
                    <button class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-bell text-lg"></i>
                        @if($avisos->count() > 0)
                        <span class="absolute top-0 right-0 w-2 h-2 rounded-full bg-red-500"></span>
                        @endif
                    </button>
                    
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold"
                             style="background: {{ $curso['cor_primaria'] }}">
                            {{ substr($aluno->nome, 0, 1) }}
                        </div>
                        <div class="hidden lg:block">
                            <p class="text-sm font-medium text-gray-900">{{ $aluno->nome }}</p>
                            <p class="text-[10px] text-gray-500">{{ $curso['nome_curto'] }}</p>
                        </div>
                    </div>
                    
                    <form method="POST" action="{{ route('curso.logout', ['curso' => $curso['id']]) }}">
                        @csrf
                        <button type="submit" class="p-2 text-gray-400 hover:text-red-500 transition-colors" title="Sair">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- ═══════════════════════════════════════════ --}}
        {{-- WELCOME + PROGRESSO                         --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl p-6 lg:p-8 border border-gray-200 shadow-sm mb-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">
                        Bem-vindo de volta, <span class="font-semibold text-gray-900">{{ $aluno->nome }}</span>
                    </p>
                    <h1 class="text-2xl font-heading font-bold mt-1" style="color: {{ $curso['cor_primaria'] }}">
                        {{ $curso['nome'] }}
                    </h1>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-bold text-gray-900">{{ $progresso }}%</span>
                    <p class="text-xs text-gray-500">concluído</p>
                </div>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="h-3 rounded-full transition-all duration-700"
                     style="width: {{ $progresso }}%; background: {{ $curso['cor_primaria'] }}">
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                {{ $aulas->where('status', 'concluida')->count() }} de {{ $aulas->count() }} aulas concluídas
            </p>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- AULA AO VIVO — DESTAQUE                     --}}
        {{-- ═══════════════════════════════════════════ --}}
        @if($aulaLive)
        <a href="{{ route('curso.aula.live', ['curso' => $curso['id'], 'aula' => $aulaLive->id]) }}"
           class="block relative rounded-2xl p-6 lg:p-8 border-2 shadow-lg mb-8 transition-all hover:scale-[1.01] group"
           style="border-color: {{ $curso['cor_primaria'] }}; background: {{ $curso['cor_primaria'] }}08">
            
            {{-- Pulso decorativo --}}
            <div class="absolute inset-0 rounded-2xl animate-pulse opacity-10"
                 style="background: {{ $curso['cor_primaria'] }}"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-red-600 uppercase tracking-widest">
                        AULA AO VIVO AGORA
                    </span>
                </div>
                
                <h2 class="text-xl lg:text-2xl font-heading font-bold text-gray-900">
                    {{ $aulaLive->titulo }}
                </h2>
                
                <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-600">
                    <span><i class="fas fa-chalkboard-teacher mr-1"></i> {{ $aulaLive->professor->nome }}</span>
                    <span><i class="fas fa-users mr-1"></i> {{ $aulaLive->viewers_count ?? 0 }} assistindo</span>
                    <span><i class="fas fa-clock mr-1"></i> Iniciou às {{ $aulaLive->horario_inicio }}</span>
                </div>
                
                <div class="mt-4 inline-flex items-center gap-2 px-6 py-3 rounded-xl text-white text-sm font-semibold group-hover:opacity-90 transition-all"
                     style="background: {{ $curso['cor_primaria'] }}">
                    <i class="fas fa-play"></i>
                    ENTRAR NA AULA
                </div>
            </div>
        </a>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- GRID DE AULAS                               --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="mb-8">
            <h3 class="font-heading text-lg font-bold text-gray-900 mb-4">Suas Aulas</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($aulas as $aula)
                @php
                    $statusConfig = [
                        'ao_vivo'   => ['label' => 'AO VIVO', 'bg' => 'bg-red-500', 'text' => 'text-white', 'pulse' => true],
                        'nova'      => ['label' => 'NOVA',     'bg' => 'bg-blue-500', 'text' => 'text-white', 'pulse' => false],
                        'concluida' => ['label' => 'CONCLUÍDA','bg' => 'bg-green-100', 'text' => 'text-green-700', 'pulse' => false],
                        'bloqueada' => ['label' => 'BLOQUEADA','bg' => 'bg-gray-100', 'text' => 'text-gray-400', 'pulse' => false],
                    ];
                    $s = $statusConfig[$aula->status] ?? $statusConfig['bloqueada'];
                @endphp
                
                <div class="bg-white rounded-2xl p-6 border border-gray-200 transition-all duration-300 group
                            {{ $aula->status === 'bloqueada' ? 'opacity-50' : 'hover:shadow-lg hover:scale-[1.02] cursor-pointer' }}"
                     @if($aula->status !== 'bloqueada')
                     onclick="window.location='{{ route('curso.aula', ['curso' => $curso['id'], 'aula' => $aula->id]) }}'"
                     @endif>
                    
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-mono text-gray-400">Aula {{ $loop->iteration }}</span>
                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full {{ $s['bg'] }} {{ $s['text'] }} {{ $s['pulse'] ? 'animate-pulse' : '' }}">
                            {{ $s['label'] }}
                        </span>
                    </div>
                    
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">
                        @if($aula->status === 'bloqueada')
                            <i class="fas fa-lock mr-1 text-gray-300"></i>
                        @elseif($aula->status === 'concluida')
                            <i class="fas fa-check-circle mr-1 text-green-500"></i>
                        @endif
                        {{ $aula->titulo }}
                    </h4>
                    
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        {{ $aula->status === 'ao_vivo' ? 'AO VIVO' : $aula->duracao }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- SIDEBAR CONTENT (Avisos + Materiais)        --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Avisos --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="font-heading text-base font-bold text-gray-900 mb-4">
                    <i class="fas fa-bullhorn mr-2" style="color: {{ $curso['cor_accent'] }}"></i>
                    Avisos do Professor
                </h3>
                @forelse($avisos as $aviso)
                <div class="py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <p class="text-sm text-gray-700">{{ $aviso->mensagem }}</p>
                    <p class="text-[10px] text-gray-400 mt-1">
                        {{ $aviso->professor->nome }} • {{ $aviso->created_at->diffForHumans() }}
                    </p>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">Nenhum aviso recente</p>
                @endforelse
            </div>
            
            {{-- Materiais --}}
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="font-heading text-base font-bold text-gray-900 mb-4">
                    <i class="fas fa-folder-open mr-2" style="color: {{ $curso['cor_accent'] }}"></i>
                    Materiais Disponíveis
                </h3>
                @forelse($materiais as $mat)
                <a href="{{ $mat->download_url }}" 
                   class="flex items-center gap-3 py-3 hover:bg-gray-50 rounded-lg px-2 transition-colors
                          {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                        <i class="fas fa-file-pdf text-red-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 truncate">{{ $mat->nome }}</p>
                        <p class="text-[10px] text-gray-400">{{ $mat->tamanho_formatado }}</p>
                    </div>
                    <i class="fas fa-download text-gray-300"></i>
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">Nenhum material disponível</p>
                @endforelse
            </div>
        </div>
    </main>
</div>
@endsection
