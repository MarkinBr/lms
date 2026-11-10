@extends('layouts.aluno')

@section('title', 'Documentos')
@section('header_title', 'Emissão de Documentos')
@section('header_subtitle', 'Solicite e baixe suas declarações e históricos')

@section('content')
<div class="space-y-8">

    @if(session('info'))
        <div class="p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 rounded shadow-sm">
            {{ session('info') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- COLUNA ESQUERDA: FORMULÁRIO --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-black text-slate-800 text-lg flex items-center gap-2">
                        <i class="fa-solid fa-file-circle-plus text-blue-600"></i> Solicitar Documento
                    </h3>
                </div>
                
                <div class="p-6">
                    <form action="{{ route('aluno.documentos.solicitar-declaracao') }}" method="POST" class="space-y-5">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Curso / Turma</label>
                            @if($matriculasAtivas->count() > 0)
                                <div class="relative">
                                    <select name="matricula_id" class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white text-slate-700" required>
                                        @foreach($matriculasAtivas as $matricula)
                                            <option value="{{ $matricula->id }}">
                                                {{ $matricula->turma->curso->titulo ?? 'Curso' }} - {{ $matricula->turma->nome ?? 'Turma' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                                </div>
                            @else
                                <div class="p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100">
                                    Nenhuma matrícula ativa encontrada.
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Tipo de Documento</label>
                            <div class="relative">
                                <select name="tipo" class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm appearance-none bg-white text-slate-700" required>
                                    <option value="matricula">Declaração de Matrícula</option>
                                    <option value="frequencia">Atestado de Frequência</option>
                                    <option value="conclusao">Certificado de Conclusão</option>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Finalidade</label>
                            <input type="text" name="finalidade" placeholder="Ex: Comprovação no trabalho" 
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm text-slate-700" required>
                        </div>

                        <button type="submit" class="w-full px-4 py-3 bg-[#003366] hover:bg-[#002244] text-white rounded-xl text-sm font-bold uppercase tracking-wide shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-paper-plane"></i> Emitir Documento
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- COLUNA DIREITA: LISTAS --}}
        <div class="lg:col-span-2 space-y-8">
            
            {{-- LISTA DE DECLARAÇÕES EMITIDAS --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-black text-slate-800 text-lg">Documentos Solicitados</h3>
                    <span class="bg-blue-50 text-blue-600 text-xs font-bold px-3 py-1 rounded-full">{{ $declaracoes->count() }}</span>
                </div>

                @if($declaracoes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Documento</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($declaracoes as $declaracao)
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 mt-1">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">
                                                @if($declaracao->tipo == 'matricula') Declaração de Matrícula
                                                @elseif($declaracao->tipo == 'frequencia') Atestado de Frequência
                                                @else Certificado de Conclusão @endif
                                            </p>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                Ref: {{ $declaracao->matricula->turma->curso->titulo ?? 'Curso' }}
                                            </p>
                                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wide">
                                                Emitido em {{ $declaracao->created_at->format('d/m/Y \à\s H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Botão VISUALIZAR --}}
                                        <a href="{{ route('documentos.visualizar', $declaracao->id) }}"
                                           target="_blank"
                                           class="px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-bold uppercase transition-colors flex items-center gap-1"
                                           title="Visualizar PDF">
                                            <i class="fa-solid fa-eye"></i> Ver
                                        </a>
                                        
                                        {{-- Botão DOWNLOAD --}}
                                        <a href="{{ route('aluno.documentos.download', $declaracao->id) }}" 
                                           class="px-3 py-2 bg-green-50 text-green-600 hover:bg-green-100 rounded-lg text-xs font-bold uppercase transition-colors flex items-center gap-1"
                                           title="Baixar PDF">
                                            <i class="fa-solid fa-download"></i> Baixar
                                        </a>
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
                        <i class="fa-solid fa-folder-open text-slate-300 text-2xl"></i>
                    </div>
                    <p class="text-slate-500 text-sm">Nenhum documento solicitado recentemente.</p>
                </div>
                @endif
            </div>

            {{-- HISTÓRICO ESCOLAR (COM FREQUÊNCIA) --}}
            @if($matriculasAtivas->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-black text-slate-800 text-lg flex items-center gap-2">
                        <i class="fa-solid fa-graduation-cap text-slate-600"></i> Histórico Escolar e de Frequência
                    </h3>
                </div>
                
                <div class="divide-y divide-slate-100">
                    @foreach($matriculasAtivas as $matricula)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-lg">
                                {{ substr($matricula->turma->curso->titulo ?? 'C', 0, 1) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">{{ $matricula->turma->curso->titulo ?? 'Curso' }}</h4>
                                <p class="text-xs text-slate-500">{{ $matricula->turma->nome ?? 'Turma' }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            {{-- Botão VISUALIZAR Histórico --}}
                            <a href="{{ route('documentos.historico.visualizar', $matricula->id) }}"
                               target="_blank"
                               class="px-3 py-2 border border-slate-200 hover:border-blue-300 text-slate-600 hover:text-blue-600 rounded-lg text-xs font-bold uppercase transition-all flex items-center gap-1">
                                <i class="fa-solid fa-eye"></i> Visualizar
                            </a>

                            {{-- Botão DOWNLOAD Histórico --}}
                            <a href="{{ route('aluno.documentos.historico', $matricula->id) }}" 
                               class="px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-bold uppercase transition-all flex items-center gap-1">
                                <i class="fa-solid fa-file-pdf"></i> Baixar PDF
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
