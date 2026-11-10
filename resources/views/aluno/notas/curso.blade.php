@extends('layouts.aluno')

@php
    $curso = $matricula->turma?->curso ?? $matricula->curso;
    $nomeCurso = $curso?->titulo ?? 'Curso em organização';
@endphp

@section('title', 'Detalhes - ' . $nomeCurso)
@section('header_title', 'Detalhes de Notas')
@section('header_subtitle', $nomeCurso)

@section('content')
<div class="space-y-6">
    
    {{-- Navegação Breadcrumb --}}
    <a href="{{ route('aluno.notas') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-[#003366] font-bold transition-colors">
        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar para todas as notas
    </a>

    {{-- Resumo do Curso --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-slate-100">
            
            <div class="pb-4 md:pb-0">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">Média do Curso</p>
                <h3 class="text-4xl font-black {{ $mediaCurso >= 7 ? 'text-green-600' : ($mediaCurso >= 5 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ number_format($mediaCurso, 2) }}
                </h3>
            </div>

            <div class="py-4 md:py-0">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">Total de Avaliações</p>
                <h3 class="text-4xl font-black text-slate-800">
                    {{ $matricula->notas->count() }}
                </h3>
            </div>

            <div class="pt-4 md:pt-0">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-2">Situação Atual</p>
                @if($mediaCurso >= 7)
                    <span class="inline-block px-4 py-2 bg-green-100 text-green-700 rounded-xl text-sm font-black uppercase tracking-wide">
                        <i class="fa-solid fa-check-circle mr-1"></i> Aprovado
                    </span>
                @elseif($mediaCurso >= 5)
                    <span class="inline-block px-4 py-2 bg-yellow-100 text-yellow-700 rounded-xl text-sm font-black uppercase tracking-wide">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Recuperação
                    </span>
                @else
                    <span class="inline-block px-4 py-2 bg-red-100 text-red-700 rounded-xl text-sm font-black uppercase tracking-wide">
                        <i class="fa-solid fa-circle-xmark mr-1"></i> Reprovado
                    </span>
                @endif
            </div>

        </div>
    </div>

    {{-- Tabela Completa --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-black text-slate-800 text-lg">Histórico Completo</h3>
        </div>

        @if($matricula->notas->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest w-16">#</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Avaliação</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Data</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Peso</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Nota</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($matricula->notas as $index => $nota)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-sm font-bold text-slate-400">
                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-slate-800">{{ $nota->tipo_avaliacao }}</p>
                            <p class="text-xs text-slate-500">{{ $nota->descricao ?? 'Sem descrição' }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-medium">
                            {{ \Carbon\Carbon::parse($nota->data_avaliacao)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-slate-600">
                            {{ $nota->peso ?? 1 }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-12 h-8 rounded-lg text-sm font-black {{ $nota->nota >= 7 ? 'bg-green-100 text-green-700' : ($nota->nota >= 5 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                {{ number_format($nota->nota, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($nota->nota >= 7)
                                <i class="fa-solid fa-circle-check text-green-500 text-lg" title="Aprovado"></i>
                            @elseif($nota->nota >= 5)
                                <i class="fa-solid fa-circle-exclamation text-yellow-500 text-lg" title="Atenção"></i>
                            @else
                                <i class="fa-solid fa-circle-xmark text-red-500 text-lg" title="Reprovado"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-inbox text-slate-300 text-2xl"></i>
            </div>
            <p class="text-slate-500 font-bold">Nenhuma nota lançada para este curso.</p>
        </div>
        @endif
    </div>
</div>
@endsection
