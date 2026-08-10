@extends('layouts.aluno')

@section('title', 'Frequência - ' . $matricula->turma->curso->nome)
@section('header_title', 'Detalhes de Frequência')
@section('header_subtitle', $matricula->turma->curso->nome)

@section('content')
<div class="space-y-6">
    
    {{-- Navegação Breadcrumb --}}
    <a href="{{ route('aluno.frequencia') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-[#003366] font-bold transition-colors">
        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar para visão geral
    </a>

    {{-- Card Principal do Curso --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-black text-slate-800">{{ $matricula->turma->curso->nome }}</h3>
            <p class="text-sm text-slate-500 font-bold">Turma: {{ $matricula->turma->nome }}</p>
        </div>
        <div class="text-right hidden md:block">
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Ano Letivo</p>
            <p class="text-lg font-black text-slate-800">{{ $matricula->turma->ano_letivo }}</p>
        </div>
    </div>

    {{-- Lista de Módulos --}}
    <div class="space-y-6">
        @foreach($modulosFrequencia as $moduloDado)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            
            {{-- Cabeçalho do Módulo --}}
            <div class="bg-slate-50 p-4 border-b border-slate-100 flex justify-between items-center">
                <h4 class="font-bold text-slate-700">{{ $moduloDado['modulo']->titulo }}</h4>
                <span class="px-3 py-1 rounded-lg text-xs font-black uppercase
                    {{ $moduloDado['percentual'] >= 75 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $moduloDado['percentual'] }}% Concluído
                </span>
            </div>

            {{-- Barra de Progresso do Módulo --}}
            <div class="px-6 pt-4">
                <div class="flex justify-between text-xs font-bold text-slate-500 mb-1">
                    <span>Progresso do Módulo</span>
                    <span>{{ $moduloDado['aulas_assistidas'] }} / {{ $moduloDado['total_aulas'] }} aulas</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2">
                    <div class="h-full rounded-full transition-all duration-500
                        {{ $moduloDado['percentual'] >= 75 ? 'bg-green-500' : 'bg-yellow-400' }}"
                        style="width: {{ $moduloDado['percentual'] }}%">
                    </div>
                </div>
            </div>

            {{-- Tabela de Aulas --}}
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <tr>
                                <th class="py-3 pl-2">Aula</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="py-3 text-center">Tempo Assistido</th>
                                <th class="py-3 text-center">Conclusão</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            @foreach($moduloDado['detalhes_aulas'] as $detalhe)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 pl-2 font-medium text-slate-700">
                                    {{ $detalhe['aula']->titulo }}
                                </td>
                                <td class="py-3 text-center">
                                    @if($detalhe['concluida'])
                                        <span class="inline-flex items-center gap-1 text-green-600 font-bold text-xs bg-green-50 px-2 py-1 rounded-lg">
                                            <i class="fa-solid fa-check-circle"></i> Concluída
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-slate-400 font-bold text-xs bg-slate-100 px-2 py-1 rounded-lg">
                                            <i class="fa-regular fa-circle"></i> Pendente
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 text-center text-slate-500 font-mono">
                                    {{ $detalhe['tempo_assistido'] > 0 ? gmdate('H:i:s', $detalhe['tempo_assistido']) : '--:--:--' }}
                                </td>
                                <td class="py-3 text-center text-slate-500">
                                    {{ $detalhe['data_conclusao'] ? $detalhe['data_conclusao']->format('d/m/Y') : '-' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection