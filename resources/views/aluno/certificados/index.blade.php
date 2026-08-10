@extends('layouts.aluno')

@section('title', 'Meus Certificados')
@section('header_title', 'Meus Certificados')
@section('header_subtitle', 'Visualize, baixe e valide seus diplomas')

@section('content')
<div class="space-y-8">

    {{-- Seção: Solicitação Pendente (Aparece primeiro se houver) --}}
    @if(isset($cursosConcluidosSemCertificado) && $cursosConcluidosSemCertificado->count() > 0)
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-6">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-blue-500 text-white rounded-xl shadow-lg shadow-blue-500/20">
                    <i class="fa-solid fa-graduation-cap text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-black text-slate-800">Certificados Disponíveis</h3>
                    <p class="text-sm text-slate-600 mb-4">Parabéns! Você concluiu os seguintes cursos e já pode solicitar a emissão do certificado.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($cursosConcluidosSemCertificado as $matricula)
                            <div class="bg-white p-4 rounded-xl border border-blue-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div>
                                    <h4 class="font-bold text-slate-800">{{ $matricula->turma->curso->nome }}</h4>
                                    <p class="text-xs text-slate-500 flex items-center gap-1 mt-1">
                                        <i class="fa-solid fa-calendar-check text-green-500"></i>
                                        Concluído em: {{ $matricula->data_conclusao ? \Carbon\Carbon::parse($matricula->data_conclusao)->format('d/m/Y') : 'Data N/A' }}
                                    </p>
                                </div>
                                <form action="{{ route('aluno.certificados.solicitar', $matricula->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wide rounded-lg shadow-md transition-all active:scale-95 flex items-center gap-2">
                                        <i class="fa-solid fa-plus"></i> Solicitar
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Seção: Certificados Emitidos --}}
    <div>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-black text-slate-800">Certificados Emitidos</h3>
            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">
                {{ $certificados->count() }} Documento(s)
            </span>
        </div>

        @if($certificados->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($certificados as $certificado)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative group hover:shadow-md transition-all">
                    
                    {{-- Faixa Decorativa --}}
                    <div class="absolute top-0 left-0 w-1 h-full bg-green-500"></div>

                    <div class="p-6">
                        {{-- Cabeçalho do Card --}}
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-2xl">
                                <i class="fa-solid fa-certificate"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Emitido em</p>
                                <p class="text-xs font-bold text-slate-700">
                                    {{ \Carbon\Carbon::parse($certificado->data_emissao)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        {{-- Título e Detalhes --}}
                        <h4 class="text-lg font-black text-slate-800 mb-2 leading-tight group-hover:text-green-700 transition-colors">
                            {{ $certificado->curso->nome ?? 'Curso não informado' }}
                        </h4>
                        
                        <div class="space-y-2 mb-6">
                            <div class="flex items-center text-xs text-slate-500">
                                <i class="fa-regular fa-clock w-5 text-center mr-2 text-slate-400"></i>
                                <span>Carga Horária: <strong>{{ $certificado->carga_horaria }} horas</strong></span>
                            </div>
                            <div class="flex items-center text-xs text-slate-500">
                                <i class="fa-solid fa-barcode w-5 text-center mr-2 text-slate-400"></i>
                                <span class="font-mono bg-slate-100 px-1 py-0.5 rounded text-slate-600">
                                    {{ $certificado->codigo_verificacao }}
                                </span>
                            </div>
                        </div>

                        {{-- Ações --}}
                        <div class="flex gap-2 pt-4 border-t border-slate-100">
                            <a href="{{ route('aluno.certificados.download', $certificado->id) }}" 
                               class="flex-1 flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-wide rounded-lg transition-colors shadow-sm">
                                <i class="fa-solid fa-download mr-2"></i> PDF
                            </a>
                            
                            {{-- Botão de Validar (Opcional, abre link externo) --}}
                            <a href="{{ route('validacao.validar', $certificado->codigo_verificacao) }}" 
                               target="_blank"
                               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold uppercase tracking-wide rounded-lg transition-colors"
                               title="Verificar Autenticidade">
                                <i class="fa-solid fa-qrcode"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            {{-- Estado Vazio --}}
            <div class="bg-white rounded-2xl border border-slate-200 border-dashed p-12 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-award text-slate-300 text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Nenhum certificado emitido</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-md mx-auto">
                    Assim que você concluir um curso e atingir os requisitos mínimos, seus certificados aparecerão aqui.
                </p>
            </div>
        @endif
    </div>

</div>
@endsection