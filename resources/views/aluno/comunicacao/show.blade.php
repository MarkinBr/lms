@extends('layouts.aluno')

@section('title', $aviso->titulo)
@section('header_title', 'Detalhes do Aviso')
@section('header_subtitle', 'Visualizando comunicado completo')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    
    {{-- Botão Voltar --}}
    <a href="{{ route('aluno.comunicacao') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-[#061a30] font-bold transition-colors">
        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar para o mural
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        {{-- Cabeçalho do Aviso --}}
        <div class="p-8 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-3 mb-4">
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide
                    {{ $aviso->tipo == 'importante' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600' }}">
                    {{ ucfirst($aviso->tipo ?? 'Informativo') }}
                </span>
                <span class="text-xs font-bold text-slate-400 flex items-center gap-1">
                    <i class="fa-regular fa-clock"></i>
                    {{ \Carbon\Carbon::parse($aviso->data_publicacao)->format('d \d\e F \d\e Y \à\s H:i') }}
                </span>
            </div>
            
            <h1 class="text-2xl md:text-3xl font-black text-slate-800 leading-tight">
                {{ $aviso->titulo }}
            </h1>
        </div>

        {{-- Corpo do Conteúdo --}}
        <div class="p-8">
            <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed">
                {!! nl2br(e($aviso->conteudo)) !!}
            </div>

            {{-- Área de Anexo --}}
            @if($aviso->anexo)
            <div class="mt-8 pt-6 border-t border-slate-100">
                <h4 class="text-sm font-bold text-slate-800 mb-3 uppercase tracking-widest">Arquivo Anexo</h4>
                <a href="{{ asset('storage/' . $aviso->anexo) }}" target="_blank" 
                   class="inline-flex items-center p-4 bg-slate-50 border border-slate-200 rounded-xl hover:bg-blue-50 hover:border-blue-200 transition-all group">
                    <div class="w-10 h-10 bg-white border border-slate-200 rounded-lg flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-file-pdf text-xl"></i>
                    </div>
                    <div class="ml-4 text-left">
                        <p class="text-sm font-bold text-slate-700 group-hover:text-blue-700">Baixar Documento</p>
                        <p class="text-xs text-slate-400">Clique para visualizar</p>
                    </div>
                    <i class="fa-solid fa-download ml-6 text-slate-300 group-hover:text-blue-500"></i>
                </a>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection