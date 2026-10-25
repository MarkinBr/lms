@extends('layouts.aluno')

@section('title', 'Secretaria')
@section('header_title', 'Secretaria Online')
@section('header_subtitle', 'Abra e acompanhe seus requerimentos')

@section('content')
<div class="space-y-8">

    {{-- Header e Ação --}}
    <div class="flex justify-end">
        <a href="{{ route('aluno.secretaria.criar') }}" class="inline-flex items-center px-6 py-3 bg-[#061a30] hover:bg-[#041322] text-white rounded-xl text-sm font-bold uppercase tracking-widest shadow-lg hover:shadow-xl transition-all active:scale-95">
            <i class="fa-solid fa-plus mr-2"></i> Novo Requerimento
        </a>
    </div>

    {{-- Estatísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        {{-- Card: Pendentes --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 relative overflow-hidden group hover:border-yellow-200 transition-colors">
            <div class="relative z-10">
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest group-hover:text-yellow-600 transition-colors">Pendentes</p>
                <h3 class="text-3xl font-black text-slate-800 mt-2">{{ $estatisticas['pendentes'] }}</h3>
            </div>
            <div class="absolute right-4 top-4 w-12 h-12 bg-yellow-50 text-yellow-500 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-hourglass-start text-xl"></i>
            </div>
        </div>

        {{-- Card: Em Análise --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 relative overflow-hidden group hover:border-blue-200 transition-colors">
            <div class="relative z-10">
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest group-hover:text-blue-600 transition-colors">Em Análise</p>
                <h3 class="text-3xl font-black text-slate-800 mt-2">{{ $estatisticas['em_analise'] }}</h3>
            </div>
            <div class="absolute right-4 top-4 w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-magnifying-glass text-xl"></i>
            </div>
        </div>

        {{-- Card: Aprovados --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 relative overflow-hidden group hover:border-green-200 transition-colors">
            <div class="relative z-10">
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest group-hover:text-green-600 transition-colors">Aprovados</p>
                <h3 class="text-3xl font-black text-slate-800 mt-2">{{ $estatisticas['aprovados'] }}</h3>
            </div>
            <div class="absolute right-4 top-4 w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-check-circle text-xl"></i>
            </div>
        </div>

        {{-- Card: Negados --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 relative overflow-hidden group hover:border-red-200 transition-colors">
            <div class="relative z-10">
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest group-hover:text-red-600 transition-colors">Negados</p>
                <h3 class="text-3xl font-black text-slate-800 mt-2">{{ $estatisticas['negados'] }}</h3>
            </div>
            <div class="absolute right-4 top-4 w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-circle-xmark text-xl"></i>
            </div>
        </div>
    </div>

    {{-- Lista de Requerimentos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-black text-slate-800 text-lg">Meus Requerimentos</h3>
        </div>

        @if($requerimentos->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Protocolo</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Tipo</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Assunto</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Data</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($requerimentos as $req)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-sm font-bold text-slate-600">
                            #{{ $req->protocolo }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                {{ $req->tipo }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-medium">
                            {{ \Str::limit($req->assunto, 40) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $req->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusClasses = [
                                    'pendente' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'em_analise' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'aprovado' => 'bg-green-100 text-green-700 border-green-200',
                                    'negado' => 'bg-red-100 text-red-700 border-red-200',
                                    'cancelado' => 'bg-slate-100 text-slate-600 border-slate-200'
                                ];
                                $class = $statusClasses[$req->status] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide border {{ $class }}">
                                {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('aluno.secretaria.show', $req->id) }}" 
                                   class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-600 hover:bg-blue-50 transition-colors"
                                   title="Ver Detalhes">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($req->status == 'pendente')
                                <form action="{{ route('aluno.secretaria.cancelar', $req->id) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Deseja cancelar este requerimento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-red-600 hover:bg-red-50 transition-colors" title="Cancelar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
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
            <h3 class="text-lg font-bold text-slate-800">Nenhum requerimento</h3>
            <p class="text-slate-500 mb-6">Você ainda não abriu nenhum protocolo na secretaria.</p>
            <a href="{{ route('aluno.secretaria.criar') }}" class="text-blue-600 font-bold hover:underline">
                Abrir meu primeiro requerimento
            </a>
        </div>
        @endif

        @if($requerimentos->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $requerimentos->links() }}
        </div>
        @endif
    </div>

</div>
@endsection