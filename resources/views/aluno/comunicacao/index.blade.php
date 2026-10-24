@extends('layouts.aluno')

@section('title', 'Comunicação')
@section('header_title', 'Mural de Avisos')
@section('header_subtitle', 'Fique por dentro das novidades da instituição')

@section('content')
<div class="space-y-8">

    {{-- Área de Ação e Estatísticas --}}
    <div class="flex flex-col md:flex-row gap-6">
        
        {{-- Card: Total de Avisos --}}
        <div class="flex-1 bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-blue-100 text-xs font-bold uppercase tracking-widest">Total de Comunicados</p>
                <h3 class="text-4xl font-black mt-2">{{ $estatisticas['total_avisos'] }}</h3>
            </div>
            <i class="fa-solid fa-bullhorn absolute -right-4 -bottom-4 text-white/10 text-8xl"></i>
        </div>

        {{-- Card: Não Lidos --}}
        <div class="flex-1 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-orange-100 text-xs font-bold uppercase tracking-widest">Não Lidos</p>
                    <h3 class="text-4xl font-black mt-2">{{ $estatisticas['nao_lidos'] }}</h3>
                </div>
                
                @if($estatisticas['nao_lidos'] > 0)
                    <button onclick="marcarComoLido()" 
                            class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-xs font-bold uppercase tracking-wide transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-check-double"></i> Marcar Lidos
                    </button>
                @endif
            </div>
            <i class="fa-solid fa-envelope absolute -right-4 -bottom-4 text-white/10 text-8xl"></i>
        </div>
    </div>

    {{-- Lista de Avisos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-black text-slate-800 text-lg">Últimas Mensagens</h3>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($avisos as $aviso)
            <div class="p-6 hover:bg-slate-50 transition-colors group relative">
                <div class="flex items-start justify-between gap-4">
                    
                    {{-- Ícone e Conteúdo --}}
                    <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-xl flex-shrink-0 flex items-center justify-center text-xl
                            {{ $aviso->tipo == 'importante' ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500' }}">
                            @if($aviso->tipo == 'importante')
                                <i class="fa-solid fa-circle-exclamation"></i>
                            @else
                                <i class="fa-solid fa-message"></i>
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <a href="{{ route('aluno.comunicacao.show', $aviso->id) }}" class="text-lg font-bold text-slate-800 group-hover:text-[#061a30] transition-colors">
                                    {{ $aviso->titulo }}
                                </a>
                                @if($aviso->tipo == 'importante')
                                    <span class="px-2 py-0.5 bg-red-100 text-red-600 rounded text-[10px] font-black uppercase tracking-wider">
                                        Importante
                                    </span>
                                @endif
                            </div>
                            
                            <p class="text-sm text-slate-500 leading-relaxed mb-2 line-clamp-2">
                                {{ \Str::limit(strip_tags($aviso->conteudo), 150) }}
                            </p>

                            <div class="flex items-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-wide">
                                <span class="flex items-center gap-1">
                                    <i class="fa-regular fa-calendar"></i> 
                                    {{ \Carbon\Carbon::parse($aviso->data_publicacao)->format('d/m/Y \à\s H:i') }}
                                </span>
                                @if($aviso->anexo)
                                    <span class="flex items-center gap-1 text-blue-500">
                                        <i class="fa-solid fa-paperclip"></i> Anexo
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Seta (apenas desktop) --}}
                    <div class="hidden md:flex items-center self-center">
                        <a href="{{ route('aluno.comunicacao.show', $aviso->id) }}" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 group-hover:bg-[#061a30] group-hover:border-[#061a30] group-hover:text-white transition-all">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                
                {{-- Link cobrindo tudo no mobile --}}
                <a href="{{ route('aluno.comunicacao.show', $aviso->id) }}" class="absolute inset-0 md:hidden"></a>
            </div>
            @empty
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-inbox text-slate-300 text-2xl"></i>
                </div>
                <p class="text-slate-500 font-bold">Nenhum aviso encontrado.</p>
            </div>
            @endforelse
        </div>

        @if($avisos->hasPages())
        <div class="p-4 bg-slate-50 border-t border-slate-200">
            {{ $avisos->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function marcarComoLido() {
    if(!confirm('Deseja marcar todos os avisos como lidos?')) return;

    fetch('{{ route('aluno.comunicacao.marcar-lido') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(error => console.error('Erro:', error));
}
</script>
@endpush