@extends('layouts.aluno')

@section('title', 'Requerimento #' . $requerimento->protocolo)
@section('header_title', 'Detalhes do Requerimento')
@section('header_subtitle', 'Visualizando protocolo #' . $requerimento->protocolo)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    
    {{-- Navegação --}}
    <a href="{{ route('aluno.secretaria') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-[#003366] font-bold transition-colors">
        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar para a lista
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Coluna Principal (Conteúdo) --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                
                {{-- Cabeçalho --}}
                <div class="p-6 border-b border-slate-100 flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-black text-slate-800 leading-tight">{{ $requerimento->assunto }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 mt-2">
                            {{ $requerimento->tipo }}
                        </span>
                    </div>
                    @php
                        $statusClasses = [
                            'pendente' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                            'em_analise' => 'bg-blue-100 text-blue-700 border-blue-200',
                            'aprovado' => 'bg-green-100 text-green-700 border-green-200',
                            'negado' => 'bg-red-100 text-red-700 border-red-200',
                            'cancelado' => 'bg-slate-100 text-slate-600 border-slate-200'
                        ];
                        $class = $statusClasses[$requerimento->status] ?? 'bg-slate-100 text-slate-600';
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide border {{ $class }}">
                        {{ ucfirst(str_replace('_', ' ', $requerimento->status)) }}
                    </span>
                </div>

                {{-- Descrição --}}
                <div class="p-6">
                    <h4 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-3">Descrição da Solicitação</h4>
                    <div class="prose prose-slate max-w-none text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100">
                        {!! nl2br(e($requerimento->descricao)) !!}
                    </div>

                    @if($requerimento->anexo)
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-3">Anexos</h4>
                        <a href="{{ asset('storage/' . $requerimento->anexo) }}" target="_blank" 
                           class="inline-flex items-center px-4 py-3 bg-white border border-slate-200 hover:border-blue-300 rounded-xl transition-all group shadow-sm">
                            <div class="w-10 h-10 bg-red-50 text-red-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-bold text-slate-700 group-hover:text-blue-600">Visualizar Anexo</p>
                                <p class="text-xs text-slate-400">Clique para baixar</p>
                            </div>
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Resposta --}}
                @if($requerimento->resposta)
                <div class="p-6 bg-slate-50 border-t border-slate-200">
                    <h4 class="text-sm font-bold text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-reply"></i> Resposta da Secretaria
                    </h4>
                    
                    <div class="bg-white p-6 rounded-xl border-l-4 shadow-sm {{ $requerimento->status == 'aprovado' ? 'border-green-500' : 'border-red-500' }}">
                        <p class="text-slate-700 leading-relaxed">{{ $requerimento->resposta }}</p>
                        
                        @if($requerimento->data_resposta)
                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-400 font-bold">
                            <i class="fa-regular fa-clock"></i>
                            Respondido em: {{ $requerimento->data_resposta->format('d/m/Y \à\s H:i') }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Coluna Lateral (Info) --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Informações</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                        <span class="text-sm text-slate-500">Protocolo</span>
                        <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded">{{ $requerimento->protocolo }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center pb-4 border-b border-slate-100">
                        <span class="text-sm text-slate-500">Data Abertura</span>
                        <span class="text-sm font-bold text-slate-700">{{ $requerimento->created_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Última Atualização</span>
                        <span class="text-sm font-bold text-slate-700">{{ $requerimento->updated_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                @if($requerimento->status == 'pendente')
                <div class="mt-6 pt-6 border-t border-slate-100">
                    <form action="{{ route('aluno.secretaria.cancelar', $requerimento->id) }}" method="POST" onsubmit="return confirm('Tem certeza?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-3 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl text-sm font-bold transition-colors">
                            <i class="fa-solid fa-ban mr-2"></i> Cancelar Solicitação
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection