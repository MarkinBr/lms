@extends('layouts.aluno')

@section('title', 'Minhas Notas')
@section('header_title', 'Minhas Notas')
@section('header_subtitle', 'Acompanhe seu desempenho acadêmico')

@section('content')
<div class="space-y-8">

    {{-- Estatísticas Gerais (Cards Modernos) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        {{-- Card: Média Geral --}}
        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-blue-100 text-xs font-bold uppercase tracking-widest">Média Geral</p>
                <div class="flex items-end gap-2 mt-2">
                    <h3 class="text-4xl font-black">{{ number_format($estatisticas['media_geral'], 2) }}</h3>
                    <span class="text-lg mb-1 opacity-80">/ 10</span>
                </div>
            </div>
            <i class="fa-solid fa-chart-pie absolute -right-4 -bottom-4 text-white/10 text-8xl"></i>
        </div>

        {{-- Card: Melhor Nota --}}
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-green-100 text-xs font-bold uppercase tracking-widest">Melhor Nota</p>
                <div class="flex items-end gap-2 mt-2">
                    <h3 class="text-4xl font-black">{{ number_format($estatisticas['melhor_nota'], 2) }}</h3>
                </div>
            </div>
            <i class="fa-solid fa-trophy absolute -right-4 -bottom-4 text-white/10 text-8xl"></i>
        </div>

        {{-- Card: Avaliações --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 relative overflow-hidden">
            <div>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Avaliações Realizadas</p>
                <h3 class="text-3xl font-black text-slate-800 mt-2">{{ $estatisticas['total_avaliacoes'] }}</h3>
            </div>
            <div class="absolute right-4 top-4 w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-file-pen text-xl"></i>
            </div>
        </div>

        {{-- Card: Aproveitamento --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 relative overflow-hidden">
            <div>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Aproveitamento</p>
                <h3 class="text-3xl font-black text-slate-800 mt-2">{{ number_format($estatisticas['aproveitamento'], 1) }}%</h3>
            </div>
            <div class="absolute right-4 top-4 w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-percent text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Lista de Cursos e Notas --}}
    <div class="space-y-6">
        <h3 class="text-lg font-black text-slate-800">Detalhamento por Curso</h3>

        @forelse($matriculas as $matricula)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
            
            {{-- Cabeçalho do Card do Curso --}}
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold text-xl">
                        {{ substr($matricula->turma->curso->nome, 0, 1) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">{{ $matricula->turma->curso->nome }}</h4>
                        <p class="text-sm text-slate-500">Turma: {{ $matricula->turma->nome }}</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-right hidden md:block">
                        <p class="text-xs text-slate-400 font-bold uppercase">Média Atual</p>
                        <p class="text-xl font-black text-slate-800">{{ number_format($matricula->notas->avg('nota'), 2) }}</p>
                    </div>
                    <a href="{{ route('aluno.notas.curso', $matricula->id) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-bold transition-colors">
                        Detalhes <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            {{-- Tabela de Notas Recentes (Preview) --}}
            @if($matricula->notas->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase">Avaliação</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase">Data</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-center">Peso</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-center">Nota</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($matricula->notas->take(3) as $nota)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-700">{{ $nota->tipo_avaliacao }}</p>
                                @if($nota->descricao)
                                    <p class="text-xs text-slate-400">{{ Str::limit($nota->descricao, 30) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ \Carbon\Carbon::parse($nota->data_avaliacao)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-slate-500">
                                {{ $nota->peso ?? 1 }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-black {{ $nota->nota >= 7 ? 'text-green-600' : ($nota->nota >= 5 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($nota->nota, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($nota->nota >= 7)
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-[10px] font-black uppercase">Aprovado</span>
                                @elseif($nota->nota >= 5)
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-[10px] font-black uppercase">Recuperação</span>
                                @else
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-[10px] font-black uppercase">Reprovado</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($matricula->notas->count() > 3)
                    <div class="p-3 text-center bg-slate-50 border-t border-slate-100">
                        <span class="text-xs text-slate-500 italic">Exibindo as 3 últimas avaliações. Clique em detalhes para ver tudo.</span>
                    </div>
                @endif
            </div>
            @else
                <div class="p-8 text-center">
                    <i class="fa-solid fa-clipboard text-slate-200 text-4xl mb-2"></i>
                    <p class="text-slate-400 font-medium">Nenhuma avaliação lançada ainda.</p>
                </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-book-open text-slate-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Nenhuma matrícula ativa</h3>
            <p class="text-slate-500 mb-6">Você não está matriculado em nenhum curso no momento.</p>
            <a href="{{ route('aluno.cursos') }}" class="px-6 py-3 bg-[#003366] text-white rounded-xl font-bold hover:bg-[#002244] transition-colors">
                Ver Cursos Disponíveis
            </a>
        </div>
        @endforelse
    </div>
</div>
@endsection