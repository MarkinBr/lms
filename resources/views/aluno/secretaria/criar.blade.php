@extends('layouts.aluno')

@section('title', 'Novo Requerimento')
@section('header_title', 'Novo Requerimento')
@section('header_subtitle', 'Abra um novo protocolo na secretaria')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    
    {{-- Navegação --}}
    <a href="{{ route('aluno.secretaria') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-[#061a30] font-bold transition-colors">
        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar para a lista
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-8">
            <h3 class="text-xl font-black text-slate-800 mb-6">Preencha os dados da solicitação</h3>

            <form action="{{ route('aluno.secretaria.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Tipo --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Tipo de Requerimento <span class="text-red-500">*</span></label>
                    <select name="tipo" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-600" required>
                        <option value="">Selecione uma opção...</option>
                        @foreach($tiposRequerimento as $key => $label)
                        <option value="{{ $key }}" {{ old('tipo') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('tipo') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                {{-- Assunto --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Assunto <span class="text-red-500">*</span></label>
                    <input type="text" name="assunto" value="{{ old('assunto') }}" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-600"
                           placeholder="Ex: Solicitação de segunda via de certificado" required>
                    @error('assunto') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                {{-- Descrição --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Descrição Detalhada <span class="text-red-500">*</span></label>
                    <textarea name="descricao" rows="6" 
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-slate-600 resize-none"
                              placeholder="Descreva sua solicitação com o máximo de detalhes possível..." required>{{ old('descricao') }}</textarea>
                    @error('descricao') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                {{-- Anexo --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Anexo (opcional)</label>
                    <div class="relative border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:bg-slate-50 transition-colors">
                        <input type="file" name="anexo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="space-y-2 pointer-events-none">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-300"></i>
                            <p class="text-sm font-bold text-slate-600">Clique ou arraste o arquivo aqui</p>
                            <p class="text-xs text-slate-400">PDF, JPG ou PNG (Máx: 5MB)</p>
                        </div>
                    </div>
                    @error('anexo') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                {{-- Info Box --}}
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-4">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-info text-blue-500 text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-blue-800">Informações Importantes</h4>
                        <ul class="text-xs text-blue-600 mt-1 list-disc list-inside space-y-1">
                            <li>Após o envio, você receberá um número de protocolo.</li>
                            <li>O prazo médio de resposta é de 5 dias úteis.</li>
                            <li>Acompanhe o status na página principal da secretaria.</li>
                        </ul>
                    </div>
                </div>

                {{-- Botões --}}
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('aluno.secretaria') }}" class="px-6 py-3 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-3 bg-[#061a30] hover:bg-[#041322] text-white rounded-xl text-sm font-bold shadow-lg transition-all active:scale-95">
                        Enviar Requerimento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection