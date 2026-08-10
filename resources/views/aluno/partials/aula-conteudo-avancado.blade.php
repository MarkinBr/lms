{{-- ═══════════════════════════════════════════════════════
     PARTIAL: Conteúdo Avançado da Aula (Estilo Estratégia)
     Inclui: Áudio, PDF, Blocos de Conteúdo, Questões Avançadas
     ═══════════════════════════════════════════════════════ --}}

{{-- ─── PLAYER DE ÁUDIO ─── --}}
@if($aula->temAudio())
<div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-purple-200 dark:border-purple-700 shadow-sm">
    <div class="flex items-center mb-4">
        <div class="p-3 bg-purple-100 dark:bg-purple-800/50 rounded-xl mr-4">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
            </svg>
        </div>
        <div>
            <h4 class="font-bold text-gray-900 dark:text-white">🎧 {{ $aula->audio_titulo ?? 'Áudio da Aula' }}</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">Ouça o conteúdo em áudio</p>
        </div>
    </div>
    <audio controls class="w-full rounded-lg" preload="metadata">
        <source src="{{ asset('storage/' . $aula->audio_url) }}" type="audio/mpeg">
        <source src="{{ $aula->audio_url }}" type="audio/mpeg">
        Seu navegador não suporta reprodução de áudio.
    </audio>
</div>
@endif

{{-- ─── VISUALIZADOR DE PDF ─── --}}
@if($aula->temPdf())
<div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-2xl p-6 border border-red-200 dark:border-red-700 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <div class="p-3 bg-red-100 dark:bg-red-800/50 rounded-xl mr-4">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-gray-900 dark:text-white">📄 {{ $aula->pdf_titulo ?? 'PDF da Aula' }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Material em PDF</p>
            </div>
        </div>
        @if($aula->permite_download)
        <a href="{{ str_starts_with($aula->pdf_url, 'http') ? $aula->pdf_url : asset('storage/' . $aula->pdf_url) }}" download class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Baixar PDF
        </a>
        @endif
    </div>
    <div class="rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-inner" style="height: 600px;">
        <iframe 
            src="{{ str_starts_with($aula->pdf_url, 'http') ? $aula->pdf_url : asset('storage/' . $aula->pdf_url) }}#toolbar=0&navpanes=0" 
            class="w-full h-full" 
            frameborder="0">
        </iframe>
    </div>
</div>
@endif

{{-- ─── BLOCOS DE CONTEÚDO ESTRUTURADO ─── --}}
@if($aula->temBlocosConteudo())
<div class="space-y-6">
    @foreach($aula->getBlocosOrdenados() as $bloco)
        @php
            $blocoConfig = [
                'destaque' => ['bg' => 'from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20', 'border' => 'border-yellow-300 dark:border-yellow-700', 'icon' => '⭐', 'color' => 'text-yellow-600'],
                'resumo' => ['bg' => 'from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20', 'border' => 'border-blue-300 dark:border-blue-700', 'icon' => '📋', 'color' => 'text-blue-600'],
                'dica' => ['bg' => 'from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20', 'border' => 'border-green-300 dark:border-green-700', 'icon' => '💡', 'color' => 'text-green-600'],
                'atencao' => ['bg' => 'from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20', 'border' => 'border-red-300 dark:border-red-700', 'icon' => '⚠️', 'color' => 'text-red-600'],
                'exemplo' => ['bg' => 'from-indigo-50 to-violet-50 dark:from-indigo-900/20 dark:to-violet-900/20', 'border' => 'border-indigo-300 dark:border-indigo-700', 'icon' => '📖', 'color' => 'text-indigo-600'],
                'jurisprudencia' => ['bg' => 'from-slate-50 to-gray-50 dark:from-slate-900/20 dark:to-gray-900/20', 'border' => 'border-slate-400 dark:border-slate-600', 'icon' => '⚖️', 'color' => 'text-slate-700'],
                'lei_seca' => ['bg' => 'from-stone-50 to-neutral-50 dark:from-stone-900/20 dark:to-neutral-900/20', 'border' => 'border-stone-400 dark:border-stone-600', 'icon' => '📜', 'color' => 'text-stone-700'],
                'exercicio' => ['bg' => 'from-teal-50 to-emerald-50 dark:from-teal-900/20 dark:to-emerald-900/20', 'border' => 'border-teal-300 dark:border-teal-700', 'icon' => '✏️', 'color' => 'text-teal-600'],
                'mnemonica' => ['bg' => 'from-pink-50 to-fuchsia-50 dark:from-pink-900/20 dark:to-fuchsia-900/20', 'border' => 'border-pink-300 dark:border-pink-700', 'icon' => '🧠', 'color' => 'text-pink-600'],
            ];
            $config = $blocoConfig[$bloco['tipo'] ?? 'destaque'] ?? $blocoConfig['destaque'];
        @endphp

        @if(in_array($bloco['tipo'] ?? '', ['video_extra']))
            {{-- Bloco de Vídeo Extra --}}
            <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-lg">
                @if(!empty($bloco['titulo']))
                <div class="p-4 bg-gray-800">
                    <h4 class="text-white font-bold">🎬 {{ $bloco['titulo'] }}</h4>
                </div>
                @endif
                <div class="aspect-video">
                    @php
                        $embedUrl = $bloco['url'] ?? '';
                        if (str_contains($embedUrl, 'youtube.com') || str_contains($embedUrl, 'youtu.be')) {
                            preg_match('/(?:v=|\/)([\w-]{11})/', $embedUrl, $m);
                            $embedUrl = isset($m[1]) ? "https://www.youtube.com/embed/{$m[1]}" : $embedUrl;
                        }
                    @endphp
                    <iframe src="{{ $embedUrl }}" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
        @elseif(($bloco['tipo'] ?? '') === 'audio')
            {{-- Bloco de Áudio Extra --}}
            <div class="bg-gradient-to-r from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-2xl p-6 border border-purple-200 dark:border-purple-700">
                <h4 class="font-bold text-gray-900 dark:text-white mb-3">🎧 {{ $bloco['titulo'] ?? 'Áudio' }}</h4>
                <audio controls class="w-full" preload="metadata">
                    <source src="{{ $bloco['url'] ?? '' }}" type="audio/mpeg">
                </audio>
            </div>
        @elseif(($bloco['tipo'] ?? '') === 'imagem')
            {{-- Bloco de Imagem/Diagrama --}}
            <div class="rounded-2xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700">
                @if(!empty($bloco['titulo']))
                <div class="p-4 bg-gray-50 dark:bg-gray-800">
                    <h4 class="font-bold text-gray-900 dark:text-white">🖼️ {{ $bloco['titulo'] }}</h4>
                </div>
                @endif
                <img src="{{ $bloco['url'] ?? '' }}" alt="{{ $bloco['titulo'] ?? '' }}" class="w-full object-contain max-h-[600px]">
            </div>
        @else
            {{-- Blocos de Texto (destaque, resumo, dica, etc.) --}}
            <div class="bg-gradient-to-r {{ $config['bg'] }} rounded-2xl border-l-4 {{ $config['border'] }} shadow-sm overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center mb-3">
                        <span class="text-2xl mr-3">{{ $config['icon'] }}</span>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $bloco['titulo'] ?? ucfirst($bloco['tipo'] ?? 'Conteúdo') }}
                        </h4>
                    </div>
                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                        {!! $bloco['conteudo'] ?? '' !!}
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endif

{{-- ─── MATERIAIS DE APOIO ─── --}}
@if($aula->temMateriais())
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Materiais de Apoio
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($aula->materiais as $material)
        @php
            $tipoIcons = [
                'pdf' => ['📄', 'from-red-500 to-red-600'],
                'slide' => ['📊', 'from-orange-500 to-orange-600'],
                'exercicio' => ['✏️', 'from-green-500 to-green-600'],
                'resumo' => ['📋', 'from-blue-500 to-blue-600'],
                'mapa_mental' => ['🧠', 'from-pink-500 to-pink-600'],
                'planilha' => ['📈', 'from-emerald-500 to-emerald-600'],
                'video' => ['🎬', 'from-purple-500 to-purple-600'],
                'audio' => ['🎧', 'from-violet-500 to-violet-600'],
                'link' => ['🔗', 'from-cyan-500 to-cyan-600'],
                'arquivo' => ['📁', 'from-gray-500 to-gray-600'],
            ];
            $icon = $tipoIcons[$material['tipo'] ?? 'arquivo'] ?? $tipoIcons['arquivo'];
        @endphp
        <a href="{{ $material['url'] ?? '#' }}" target="_blank" 
           class="interactive-card flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 transition-all group">
            <div class="p-2.5 bg-gradient-to-br {{ $icon[1] }} rounded-lg mr-3 text-white text-lg flex-shrink-0">
                {{ $icon[0] }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-900 dark:text-white text-sm truncate">{{ $material['titulo'] ?? 'Material' }}</div>
                @if(!empty($material['descricao']))
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $material['descricao'] }}</div>
                @endif
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- ─── QUESTÕES AVANÇADAS (ESTILO ESTRATÉGIA) ─── --}}
@if($aula->temQuestoes())
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden" id="questoes-section">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6">
        <h3 class="text-xl font-bold text-white flex items-center">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Questões da Aula
        </h3>
        <p class="text-blue-100 text-sm mt-1">
            {{ count($aula->questoes) }} questão(ões) 
            @if($aula->nota_minima_aprovacao)
                • Nota mínima: {{ $aula->nota_minima_aprovacao }}%
            @endif
        </p>
    </div>

    <div class="p-6 space-y-8" id="questoes-container">
        @foreach($aula->questoes as $qi => $questao)
        <div class="questao-item border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden" data-questao-index="{{ $qi }}">
            {{-- Cabeçalho da questão --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 flex items-start justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-lg mr-3 flex-shrink-0">
                        {{ $qi + 1 }}
                    </div>
                    <div>
                        @if(!empty($questao['banca']))
                        <span class="text-xs font-semibold px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-full">
                            {{ $questao['banca'] }}
                        </span>
                        @endif
                        <span class="text-xs text-gray-500 ml-2">
                            {{ $questao['pontos'] ?? 1 }} ponto(s)
                        </span>
                    </div>
                </div>
            </div>

            {{-- Enunciado --}}
            <div class="p-6">
                <div class="prose dark:prose-invert max-w-none mb-6 text-gray-800 dark:text-gray-200">
                    {!! $questao['enunciado'] ?? '' !!}
                </div>

                {{-- Alternativas (Múltipla Escolha) --}}
                @if(($questao['tipo'] ?? 'multipla_escolha') === 'multipla_escolha')
                <div class="space-y-3">
                    @foreach($questao['alternativas'] ?? [] as $ai => $alt)
                    <div class="quiz-option-advanced border-2 border-gray-200 dark:border-gray-600 rounded-xl p-4 cursor-pointer transition-all hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10"
                         onclick="selecionarAlternativa({{ $qi }}, {{ $ai }})"
                         data-questao="{{ $qi }}" data-alternativa="{{ $ai }}" data-correta="{{ ($alt['correta'] ?? false) ? 'true' : 'false' }}">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full border-2 border-gray-400 dark:border-gray-500 mr-4 flex items-center justify-center font-bold text-gray-600 dark:text-gray-400 flex-shrink-0 transition-all alternativa-letra">
                                {{ $alt['letra'] ?? chr(65 + $ai) }}
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 flex-1">{{ $alt['texto'] ?? '' }}</span>
                            <div class="alternativa-feedback hidden ml-3 flex-shrink-0"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Verdadeiro/Falso ou Certo/Errado --}}
                @if(in_array($questao['tipo'] ?? '', ['verdadeiro_falso', 'certo_errado']))
                @php $labels = ($questao['tipo'] === 'verdadeiro_falso') ? ['Verdadeiro', 'Falso'] : ['Certo', 'Errado']; @endphp
                <div class="flex space-x-4">
                    <button class="flex-1 quiz-option-advanced border-2 border-gray-200 dark:border-gray-600 rounded-xl p-4 text-center font-semibold cursor-pointer transition-all hover:border-green-400 hover:bg-green-50"
                            onclick="selecionarVF({{ $qi }}, true)"
                            data-questao="{{ $qi }}" data-valor="true" data-correta="{{ ($questao['resposta_correta'] ?? false) ? 'true' : 'false' }}">
                        ✅ {{ $labels[0] }}
                    </button>
                    <button class="flex-1 quiz-option-advanced border-2 border-gray-200 dark:border-gray-600 rounded-xl p-4 text-center font-semibold cursor-pointer transition-all hover:border-red-400 hover:bg-red-50"
                            onclick="selecionarVF({{ $qi }}, false)"
                            data-questao="{{ $qi }}" data-valor="false" data-correta="{{ !($questao['resposta_correta'] ?? false) ? 'true' : 'false' }}">
                        ❌ {{ $labels[1] }}
                    </button>
                </div>
                @endif

                {{-- Dissertativa --}}
                @if(($questao['tipo'] ?? '') === 'dissertativa')
                <textarea class="w-full p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-0 resize-none"
                    rows="6" placeholder="Escreva sua resposta..." data-questao="{{ $qi }}"></textarea>
                @endif

                {{-- Comentário do Professor (inicialmente oculto) --}}
                @if(!empty($questao['comentario_professor']))
                <div class="comentario-professor hidden mt-6 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4" data-questao="{{ $qi }}">
                    <h5 class="font-bold text-amber-800 dark:text-amber-300 mb-2 flex items-center">
                        💡 Comentário do Professor
                    </h5>
                    <div class="prose dark:prose-invert max-w-none text-sm">
                        {!! $questao['comentario_professor'] !!}
                    </div>
                    @if(!empty($questao['referencia_legal']))
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 font-mono bg-gray-100 dark:bg-gray-700 rounded-lg p-2">
                        📜 {{ $questao['referencia_legal'] }}
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Rodapé do Quiz --}}
    <div class="p-6 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
        <div id="questoes-resultado" class="text-lg font-semibold text-gray-900 dark:text-white"></div>
        <div class="flex space-x-3">
            <button onclick="mostrarGabarito()" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-800 dark:text-white font-semibold rounded-xl transition">
                Ver Gabarito
            </button>
            <button onclick="enviarQuestoes()" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                Enviar Respostas
            </button>
        </div>
    </div>
</div>

{{-- JavaScript para Questões Avançadas --}}
<script>
const respostasAluno = {};

function selecionarAlternativa(qi, ai) {
    respostasAluno[qi] = ai;
    // Visual feedback
    document.querySelectorAll(`[data-questao="${qi}"][data-alternativa]`).forEach(el => {
        el.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        el.querySelector('.alternativa-letra').classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
    });
    const selected = document.querySelector(`[data-questao="${qi}"][data-alternativa="${ai}"]`);
    if (selected) {
        selected.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        selected.querySelector('.alternativa-letra').classList.add('bg-blue-600', 'text-white', 'border-blue-600');
    }
}

function selecionarVF(qi, valor) {
    respostasAluno[qi] = valor;
    document.querySelectorAll(`[data-questao="${qi}"][data-valor]`).forEach(el => {
        el.classList.remove('border-blue-500', 'bg-blue-50');
    });
    const selected = document.querySelector(`[data-questao="${qi}"][data-valor="${valor}"]`);
    if (selected) {
        selected.classList.add('border-blue-500', 'bg-blue-50');
    }
}

function enviarQuestoes() {
    const questoes = @json($aula->questoes);
    let acertos = 0;
    let total = questoes.length;

    questoes.forEach((q, qi) => {
        const resposta = respostasAluno[qi];
        let correta = false;

        if (q.tipo === 'multipla_escolha') {
            const altCorreta = (q.alternativas || []).findIndex(a => a.correta);
            correta = (resposta === altCorreta);
            
            document.querySelectorAll(`[data-questao="${qi}"][data-alternativa]`).forEach((el, ai) => {
                el.classList.remove('border-blue-500', 'bg-blue-50');
                if (q.alternativas[ai] && q.alternativas[ai].correta) {
                    el.classList.add('border-green-500', 'bg-green-50');
                    el.querySelector('.alternativa-feedback').innerHTML = '<span class="text-green-600 font-bold">✓</span>';
                    el.querySelector('.alternativa-feedback').classList.remove('hidden');
                } else if (ai === resposta && !correta) {
                    el.classList.add('border-red-500', 'bg-red-50');
                    el.querySelector('.alternativa-feedback').innerHTML = '<span class="text-red-600 font-bold">✗</span>';
                    el.querySelector('.alternativa-feedback').classList.remove('hidden');
                }
            });
        } else if (['verdadeiro_falso', 'certo_errado'].includes(q.tipo)) {
            correta = (resposta === q.resposta_correta);
        }

        if (correta) acertos++;

        // Mostrar comentário do professor
        const comentario = document.querySelector(`.comentario-professor[data-questao="${qi}"]`);
        if (comentario) comentario.classList.remove('hidden');
    });

    const nota = total > 0 ? Math.round((acertos / total) * 100) : 0;
    const resultado = document.getElementById('questoes-resultado');
    resultado.innerHTML = `<span class="${nota >= ({{ $aula->nota_minima_aprovacao ?? 70 }}) ? 'text-green-600' : 'text-red-600'}">
        Resultado: ${acertos}/${total} (${nota}%)
    </span>`;

    // Salvar via AJAX
    fetch(`/portal/aula-v2/{{ $aula->id }}/quiz`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ score: nota, answers: respostasAluno, attempts: {} })
    });
}

function mostrarGabarito() {
    const questoes = @json($aula->questoes);
    questoes.forEach((q, qi) => {
        if (q.tipo === 'multipla_escolha') {
            document.querySelectorAll(`[data-questao="${qi}"][data-alternativa]`).forEach((el, ai) => {
                if (q.alternativas[ai] && q.alternativas[ai].correta) {
                    el.classList.add('border-green-500', 'bg-green-50');
                    el.querySelector('.alternativa-feedback').innerHTML = '<span class="text-green-600 font-bold">✓ Correta</span>';
                    el.querySelector('.alternativa-feedback').classList.remove('hidden');
                }
            });
        }
        const comentario = document.querySelector(`.comentario-professor[data-questao="${qi}"]`);
        if (comentario) comentario.classList.remove('hidden');
    });
}
</script>
@endif
