@extends('layouts.aluno')

@section('title', 'Frequência')
@section('header_title', 'Minha Frequência')
@section('header_subtitle', 'Acompanhe sua presença e progresso nas aulas')

@section('content')
<div class="space-y-8">

    {{-- Resumo Geral (Cards Topo) --}}
    @if($dadosFrequencia->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-blue-100 text-xs font-bold uppercase tracking-widest">Total de Aulas Assistidas</p>
                    <h3 class="text-4xl font-black mt-2">
                        {{ $dadosFrequencia->sum('aulas_assistidas') }}
                    </h3>
                </div>
                <i class="fa-solid fa-play absolute -right-4 -bottom-4 text-white/10 text-8xl"></i>
            </div>

            <div class="bg-gradient-to-br from-purple-600 to-purple-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-purple-100 text-xs font-bold uppercase tracking-widest">Média de Frequência</p>
                    <h3 class="text-4xl font-black mt-2">
                        {{ number_format($dadosFrequencia->avg('percentual'), 1) }}%
                    </h3>
                </div>
                <i class="fa-solid fa-chart-simple absolute -right-4 -bottom-4 text-white/10 text-8xl"></i>
            </div>
        </div>
    @endif

    {{-- Lista de Cursos --}}
    <div class="space-y-6">
        <h3 class="text-lg font-black text-slate-800">Detalhamento por Curso</h3>

        @forelse($dadosFrequencia as $dados)
        @php
            $curso = $dados['matricula']->turma?->curso ?? $dados['matricula']->curso;
            $nomeCurso = $curso?->titulo ?? 'Curso em organização';
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
            
            {{-- Cabeçalho do Card --}}
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold text-xl">
                        {{ substr($nomeCurso, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">{{ $nomeCurso }}</h4>
                        <p class="text-sm text-slate-500">
                            @if($dados['matricula']->turma)
                                Turma: {{ $dados['matricula']->turma->nome ?? 'Sem identificação' }}
                            @else
                                Matrícula ativa · turma em organização
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wide
                        {{ $dados['percentual'] >= 75 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $dados['percentual'] }}% - {{ $dados['status']['label'] }}
                    </span>
                    @if($dados['matricula']->turma)
                        <a href="{{ route('aluno.frequencia.curso', $dados['matricula']->id) }}"
                           class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold transition-colors">
                            Detalhes <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Corpo do Card --}}
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                
                {{-- Barra de Progresso --}}
                <div>
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-sm font-bold text-slate-600">Progresso</span>
                        <span class="text-xs font-bold text-slate-400">
                            {{ $dados['aulas_assistidas'] }} de {{ $dados['total_aulas'] }} aulas
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500
                            {{ $dados['percentual'] >= 75 ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-yellow-400 to-yellow-500' }}"
                            style="width: {{ $dados['percentual'] }}%">
                        </div>
                    </div>
                </div>

                {{-- Status / Alerta --}}
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    @if($dados['percentual'] < 75)
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-yellow-500 mt-1"></i>
                            <div>
                                <p class="text-sm font-bold text-slate-700">Atenção Necessária</p>
                                <p class="text-xs text-slate-500 mt-1">Sua frequência está abaixo de 75%. Assista mais aulas para garantir sua aprovação.</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <div>
                                <p class="text-sm font-bold text-slate-700">Frequência Adequada</p>
                                <p class="text-xs text-slate-500 mt-1">Parabéns! Você está cumprindo os requisitos de frequência deste curso.</p>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-clipboard-list text-slate-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Nenhuma matrícula ativa</h3>
            <p class="text-slate-500 mb-6">Você não possui cursos ativos para acompanhar a frequência.</p>
            <a href="{{ route('aluno.cursos') }}" class="px-6 py-3 bg-[#003366] text-white rounded-xl font-bold hover:bg-[#002244] transition-colors">
                Ver Cursos Disponíveis
            </a>
        </div>
        @endforelse
    </div>

    {{-- Informações Adicionais --}}
    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6">
        <h4 class="font-bold text-blue-800 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-circle-info"></i> Informações Importantes
        </h4>
        <ul class="space-y-2 text-sm text-blue-700/80 list-disc list-inside">
            <li>A frequência mínima para aprovação é de <strong>75%</strong>.</li>
            <li>A frequência é calculada automaticamente ao concluir 100% de visualização de uma aula.</li>
            <li>Acompanhe este painel regularmente para evitar reprovação por faltas.</li>
        </ul>
    </div>

</div>
@endsection
