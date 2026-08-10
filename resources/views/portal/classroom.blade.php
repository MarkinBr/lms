{{-- 
    CETSADP — Sala de Aula (Blade Template)
    
    Variáveis:
      $curso    — array de configuração visual
      $aula     — model da aula atual
      $aluno    — model do aluno autenticado
      $mensagens — últimas mensagens do chat (eager loaded)
      $perguntas — perguntas do fórum (ordered by votes)
--}}
@extends('layouts.portal')

@section('title', $aula->titulo . ' — ' . $curso['nome'])

@push('styles')
<style>
    .video-container {
        position: relative;
        padding-top: 56.25%; /* 16:9 */
        border-radius: 1rem;
        overflow: hidden;
    }
    .video-container > * {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
    }
    .chat-scroll {
        max-height: 400px;
        overflow-y: auto;
        scroll-behavior: smooth;
    }
    .chat-scroll::-webkit-scrollbar { width: 4px; }
    .chat-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 2px; }
    
    .tab-active {
        background: {{ $curso['cor_primaria'] }};
        color: white;
        box-shadow: 0 4px 12px {{ $curso['cor_primaria'] }}40;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50">
    
    {{-- TOP BAR SIMPLIFICADA --}}
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <div class="flex items-center gap-3">
                    <a href="{{ route('curso.dashboard', ['curso' => $curso['id']]) }}"
                       class="p-2 text-gray-400 hover:text-gray-600 transition-colors" title="Voltar">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $aula->titulo }}</p>
                        <p class="text-[10px] text-gray-500">{{ $curso['nome_curto'] }} • Aula {{ $aula->numero }}</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    @if($aula->is_live)
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 border border-red-200">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        <span class="text-[11px] font-bold text-red-600">AO VIVO</span>
                        <span class="text-[11px] text-red-400">• {{ $aula->viewers_count ?? 0 }} assistindo</span>
                    </div>
                    @endif
                    
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[10px] font-bold"
                         style="background: {{ $curso['cor_primaria'] }}">
                        {{ substr($aluno->nome, 0, 1) }}
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- ═══════════════════════════════════════════ --}}
            {{-- COLUNA PRINCIPAL — Vídeo + Tabs              --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- PLAYER DE VÍDEO --}}
                <div class="video-container bg-gray-900 shadow-xl">
                    @if($aula->is_live && $aula->live_embed_url)
                        {{-- Embed do stream ao vivo (YouTube/Vimeo/Custom) --}}
                        <iframe src="{{ $aula->live_embed_url }}" 
                                frameborder="0" 
                                allow="autoplay; encrypted-media; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    @elseif($aula->video_url)
                        {{-- Vídeo gravado --}}
                        <video controls preload="metadata" class="bg-black">
                            <source src="{{ $aula->video_url }}" type="video/mp4">
                            Seu navegador não suporta vídeo HTML5.
                        </video>
                    @else
                        {{-- Placeholder --}}
                        <div class="flex flex-col items-center justify-center bg-gradient-to-br text-white"
                             style="background: linear-gradient(135deg, {{ $curso['cor_primaria'] }}, {{ $curso['cor_secundaria'] }})">
                            <div class="w-20 h-20 rounded-full bg-white/10 flex items-center justify-center mb-4 border border-white/20">
                                <i class="fas fa-play text-3xl text-white/80"></i>
                            </div>
                            <h3 class="font-heading text-xl">{{ $aula->titulo }}</h3>
                            <p class="text-sm opacity-60 mt-2">{{ $aula->professor->nome }} • Aula {{ $aula->numero }}</p>
                        </div>
                    @endif
                </div>
                
                {{-- TABS: Chat | Anotações | Perguntas --}}
                <div>
                    <div class="flex gap-2 mb-4">
                        <button onclick="switchTab('chat')" id="tab-chat"
                                class="tab-btn px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 tab-active">
                            💬 Chat ao Vivo
                        </button>
                        <button onclick="switchTab('notes')" id="tab-notes"
                                class="tab-btn px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50">
                            📝 Anotações
                        </button>
                        <button onclick="switchTab('forum')" id="tab-forum"
                                class="tab-btn px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50">
                            ❓ Perguntas
                        </button>
                    </div>
                    
                    {{-- CHAT PANEL --}}
                    <div id="panel-chat" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="chat-scroll p-4 space-y-3" id="chat-messages">
                            @foreach($mensagens as $msg)
                            <div class="flex gap-3 p-3 rounded-xl {{ $msg->is_professor ? 'bg-amber-50 border border-amber-100' : '' }}">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0
                                            {{ $msg->is_professor ? 'text-white' : 'bg-gray-100 text-gray-500' }}"
                                     @if($msg->is_professor) style="background: {{ $curso['cor_primaria'] }}" @endif>
                                    {{ substr($msg->user->nome, 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold {{ $msg->is_professor ? '' : 'text-gray-900' }}"
                                              @if($msg->is_professor) style="color: {{ $curso['cor_primaria'] }}" @endif>
                                            {{ $msg->user->nome }}
                                        </span>
                                        @if($msg->is_professor)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold text-white"
                                              style="background: {{ $curso['cor_accent'] }}">
                                            PROFESSOR
                                        </span>
                                        @endif
                                        <span class="text-[10px] text-gray-400">{{ $msg->created_at->format('H:i') }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ $msg->mensagem }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        {{-- Input do chat --}}
                        <div class="border-t border-gray-200 p-4">
                            <form id="chat-form" class="flex gap-3">
                                @csrf
                                <input type="text" 
                                       id="chat-input"
                                       placeholder="Digite sua mensagem..." 
                                       class="flex-1 px-4 py-3 bg-gray-50 rounded-xl text-sm border border-gray-200 focus:ring-2 focus:outline-none transition-all"
                                       style="--tw-ring-color: {{ $curso['cor_primaria'] }}40"
                                       autocomplete="off">
                                <button type="submit" 
                                        class="px-6 py-3 rounded-xl text-sm font-semibold text-white hover:opacity-90 shadow-lg transition-all"
                                        style="background: {{ $curso['cor_primaria'] }}">
                                    Enviar
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    {{-- NOTES PANEL --}}
                    <div id="panel-notes" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hidden">
                        <div class="flex items-center justify-between">
                            <h4 class="font-heading text-base text-gray-900">Minhas Anotações</h4>
                            <span class="text-[10px] text-gray-400 bg-gray-50 px-3 py-1.5 rounded-full font-medium" id="save-status">
                                ✓ Salvo automaticamente
                            </span>
                        </div>
                        <textarea id="notes-textarea"
                                  class="w-full h-64 p-5 bg-gray-50 rounded-xl text-sm border border-gray-200 resize-none focus:ring-2 focus:outline-none transition-all leading-relaxed"
                                  style="--tw-ring-color: {{ $curso['cor_primaria'] }}40"
                                  placeholder="Escreva suas anotações sobre esta aula..."
                        >{{ $aluno->anotacoes()->where('aula_id', $aula->id)->first()?->conteudo ?? '' }}</textarea>
                        <div class="flex gap-3">
                            <button onclick="exportPDF()" 
                                    class="px-5 py-3 rounded-xl text-sm font-semibold text-white hover:opacity-90 shadow-lg transition-all"
                                    style="background: {{ $curso['cor_accent'] }}">
                                📄 Exportar PDF
                            </button>
                            <button onclick="copyNotes()"
                                    class="px-5 py-3 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-all">
                                📋 Copiar Texto
                            </button>
                        </div>
                    </div>
                    
                    {{-- FORUM PANEL --}}
                    <div id="panel-forum" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hidden">
                        <h4 class="font-heading text-base text-gray-900">Perguntas ao Professor</h4>
                        
                        @foreach($perguntas as $q)
                        <div class="flex items-start gap-4 bg-gray-50 rounded-xl p-5 border border-gray-100 transition-all hover:border-gray-300">
                            <div class="flex flex-col items-center gap-1 shrink-0">
                                <form method="POST" action="{{ route('curso.aula', ['curso' => $curso['id'], 'aula' => $aula->id]) }}/votar/{{ $q->id }}">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-blue-500 transition-colors">
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                </form>
                                <span class="text-xs font-bold text-gray-700">{{ $q->votos }}</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $q->pergunta }}</p>
                                <span class="text-[11px] text-gray-400 mt-1 inline-block">
                                    {{ $q->user->nome }} • {{ $q->created_at->diffForHumans() }}
                                </span>
                                @if($q->resposta)
                                <div class="mt-3 pl-4 border-l-2 py-2" style="border-color: {{ $curso['cor_accent'] }}">
                                    <p class="text-sm text-gray-700"><strong>{{ $q->professor->nome }}:</strong> {{ $q->resposta }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        
                        {{-- Nova pergunta --}}
                        <form method="POST" action="{{ route('curso.aula', ['curso' => $curso['id'], 'aula' => $aula->id]) }}/perguntar" class="flex gap-3">
                            @csrf
                            <input type="text" name="pergunta"
                                   placeholder="Faça uma pergunta ao professor..." 
                                   class="flex-1 px-4 py-3 bg-gray-50 rounded-xl text-sm border border-gray-200 focus:ring-2 focus:outline-none transition-all"
                                   style="--tw-ring-color: {{ $curso['cor_primaria'] }}40">
                            <button type="submit" 
                                    class="px-6 py-3 rounded-xl text-sm font-semibold text-white hover:opacity-90 shadow-lg transition-all"
                                    style="background: {{ $curso['cor_primaria'] }}">
                                Perguntar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════ --}}
            {{-- SIDEBAR — Informações da Aula               --}}
            {{-- ═══════════════════════════════════════════ --}}
            <div class="space-y-6">
                {{-- Info da Aula --}}
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h4 class="font-heading text-sm font-bold text-gray-900 mb-4">Sobre esta aula</h4>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <i class="fas fa-chalkboard-teacher w-5 text-center" style="color: {{ $curso['cor_accent'] }}"></i>
                            <span>{{ $aula->professor->nome }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <i class="fas fa-clock w-5 text-center" style="color: {{ $curso['cor_accent'] }}"></i>
                            <span>{{ $aula->duracao }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <i class="fas fa-calendar-alt w-5 text-center" style="color: {{ $curso['cor_accent'] }}"></i>
                            <span>{{ $aula->data_formatada }}</span>
                        </div>
                    </div>
                    
                    @if($aula->descricao)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500 leading-relaxed">{{ $aula->descricao }}</p>
                    </div>
                    @endif
                </div>
                
                {{-- Materiais da Aula --}}
                @if($aula->materiais->count() > 0)
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h4 class="font-heading text-sm font-bold text-gray-900 mb-4">
                        <i class="fas fa-paperclip mr-1" style="color: {{ $curso['cor_accent'] }}"></i>
                        Materiais
                    </h4>
                    @foreach($aula->materiais as $mat)
                    <a href="{{ $mat->download_url }}" 
                       class="flex items-center gap-3 py-2.5 hover:bg-gray-50 rounded-lg px-2 transition-colors">
                        <i class="fas fa-file-pdf text-red-400"></i>
                        <span class="text-sm text-gray-600 flex-1">{{ $mat->nome }}</span>
                        <i class="fas fa-download text-gray-300 text-xs"></i>
                    </a>
                    @endforeach
                </div>
                @endif
                
                {{-- Próxima Aula --}}
                @if($proximaAula = $aulas->where('numero', '>', $aula->numero)->first())
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h4 class="font-heading text-sm font-bold text-gray-900 mb-3">Próxima Aula</h4>
                    <p class="text-sm font-medium text-gray-700">{{ $proximaAula->titulo }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $proximaAula->data_formatada }}</p>
                    <a href="{{ route('curso.aula', ['curso' => $curso['id'], 'aula' => $proximaAula->id]) }}"
                       class="inline-flex items-center gap-1 mt-3 text-sm font-medium hover:underline"
                       style="color: {{ $curso['cor_primaria'] }}">
                        Ver aula <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
    // ── Tab Switching ──
    function switchTab(tabId) {
        document.querySelectorAll('[id^="panel-"]').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.className = 'tab-btn px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 bg-white text-gray-600 border border-gray-200 hover:bg-gray-50';
        });
        document.getElementById('panel-' + tabId).classList.remove('hidden');
        document.getElementById('tab-' + tabId).className = 'tab-btn px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 tab-active';
    }

    // ── Chat WebSocket (Laravel Echo + Pusher) ──
    @if($aula->is_live)
    if (typeof Echo !== 'undefined') {
        Echo.channel('aula.{{ $aula->id }}')
            .listen('NovaMensagemChat', (e) => {
                appendMessage(e.mensagem);
                scrollChat();
            });
    }
    @endif

    document.getElementById('chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        
        fetch('/api/aula/{{ $aula->id }}/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mensagem: msg })
        });
        
        input.value = '';
    });

    function scrollChat() {
        const el = document.getElementById('chat-messages');
        el.scrollTop = el.scrollHeight;
    }
    scrollChat();

    // ── Auto-save Anotações ──
    let saveTimeout;
    document.getElementById('notes-textarea')?.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        document.getElementById('save-status').textContent = '⏳ Salvando...';
        
        saveTimeout = setTimeout(() => {
            fetch('/api/aula/{{ $aula->id }}/anotacoes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ conteudo: this.value })
            }).then(() => {
                document.getElementById('save-status').textContent = '✓ Salvo automaticamente';
            });
        }, 1500);
    });

    // ── Export PDF ──
    function exportPDF() {
        const text = document.getElementById('notes-textarea').value;
        window.open('/api/aula/{{ $aula->id }}/anotacoes/pdf?text=' + encodeURIComponent(text), '_blank');
    }

    // ── Copy Notes ──
    function copyNotes() {
        navigator.clipboard.writeText(document.getElementById('notes-textarea').value);
        alert('Anotações copiadas!');
    }
</script>
@endpush
