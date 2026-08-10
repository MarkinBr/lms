<x-filament-panels::page>
    {{-- Barra de Progresso no Topo --}}
    <div class="mb-6 bg-white rounded-xl shadow-lg p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-gray-900">{{ $curso->titulo }}</h2>
                <span class="text-sm text-gray-600">{{ $modulo->titulo }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-gray-700">
                    {{ $aulasConcluidasCurso }} de {{ $totalAulasCurso }} aulas concluídas
                </span>
                <span class="text-sm font-bold text-green-600">{{ number_format($progressoCurso, 1) }}%</span>
            </div>
        </div>
        
        {{-- Barra de Progresso do Curso --}}
        <div class="relative w-full h-3 bg-gray-200 rounded-full overflow-hidden">
            <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-green-500 to-green-600 transition-all duration-500"
                 style="width: {{ $progressoCurso }}%">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        {{-- Área Principal: Conteúdo da Aula --}}
        <div class="lg:col-span-3 space-y-6">
            
            {{-- Cabeçalho da Aula --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">
                                Aula {{ $numeroAulaAtual }} de {{ $totalAulasCurso }}
                            </span>
                            @if($aula->tipo_conteudo)
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">
                                {{ ucfirst($aula->tipo_conteudo) }}
                            </span>
                            @endif
                            @if($aula->duracao)
                            <span class="text-gray-500 text-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $aula->duracao }}
                            </span>
                            @endif
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $aula->titulo }}</h1>
                        @if($aula->descricao)
                        <p class="text-gray-600">{{ $aula->descricao }}</p>
                        @endif
                    </div>
                    
                    {{-- Botão Marcar como Concluída --}}
                    @if(!isset($progresso[$aula->id]) || !$progresso[$aula->id])
                    <button 
                        wire:click="marcarComoConcluida"
                        class="ml-4 bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-lg transition-all transform hover:scale-105 flex items-center gap-2 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Concluir Aula
                    </button>
                    @else
                    <div class="ml-4 bg-green-100 text-green-800 font-semibold px-6 py-3 rounded-lg flex items-center gap-2 border-2 border-green-500">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        ✓ Concluída
                    </div>
                    @endif
                </div>

                {{-- Barra de Progresso da Aula Atual --}}
                <div class="mt-4 pt-4 border-t">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Seu progresso nesta aula:</span>
                        <span class="text-sm font-bold text-green-600">
                            {{ isset($progresso[$aula->id]) && $progresso[$aula->id] ? '100%' : '0%' }}
                        </span>
                    </div>
                    <div class="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-green-400 to-green-600 transition-all duration-500"
                             style="width: {{ isset($progresso[$aula->id]) && $progresso[$aula->id] ? '100' : '0' }}%">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conteúdo da Aula (Texto Rico + Vídeo Incorporado) --}}
            <div class="bg-white rounded-xl shadow-lg p-8">
                <div class="prose prose-lg max-w-none">
                    @if($aula->conteudo_texto)
                        {{-- Renderiza o HTML do conteúdo --}}
                        <div class="conteudo-aula">
                            {!! $aula->conteudo_texto !!}
                        </div>
                    @else
                        {{-- Fallback: Mostra vídeo se não tiver conteúdo de texto --}}
                        @if($aula->video_url)
                        <div class="mb-6">
                            @if(str_contains($aula->video_url, 'youtube.com') || str_contains($aula->video_url, 'youtu.be'))
                                @php
                                    preg_match('/(?:youtube\\.com\\/(?:[^\\/]+\\/.+\\/|(?:v|e(?:mbed)?)\\/|.*[?&]v=)|youtu\\.be\\/)([^"&?\\/\\s]{11})/', $aula->video_url, $matches);
                                    $videoId = $matches[1] ?? null;
                                @endphp
                                @if($videoId)
                                <div class="relative w-full rounded-xl overflow-hidden shadow-2xl" style="padding-top: 56.25%;">
                                    <iframe 
                                        src="https://www.youtube.com/embed/{{ $videoId }}?rel=0&modestbranding=1"
                                        class="absolute top-0 left-0 w-full h-full"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen>
                                    </iframe>
                                </div>
                                @endif
                            @elseif(str_contains($aula->video_url, 'vimeo.com'))
                                @php
                                    preg_match('/vimeo\\.com\\/(\\d+)/', $aula->video_url, $matches);
                                    $videoId = $matches[1] ?? null;
                                @endphp
                                @if($videoId)
                                <div class="relative w-full rounded-xl overflow-hidden shadow-2xl" style="padding-top: 56.25%;">
                                    <iframe 
                                        src="https://player.vimeo.com/video/{{ $videoId }}"
                                        class="absolute top-0 left-0 w-full h-full"
                                        frameborder="0"
                                        allow="autoplay; fullscreen; picture-in-picture"
                                        allowfullscreen>
                                    </iframe>
                                </div>
                                @endif
                            @else
                                <div class="relative w-full rounded-xl overflow-hidden shadow-2xl" style="padding-top: 56.25%;">
                                    <iframe 
                                        src="{{ $aula->video_url }}"
                                        class="absolute top-0 left-0 w-full h-full"
                                        frameborder="0"
                                        allowfullscreen>
                                    </iframe>
                                </div>
                            @endif
                        </div>
                        @endif

                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p>Nenhum conteúdo de texto disponível para esta aula.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Materiais de Apoio --}}
            @if($aula->materiais)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Materiais de Apoio
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach(json_decode($aula->materiais) as $material)
                    <a href="{{ Storage::url($material->caminho) }}" 
                       target="_blank"
                       class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg hover:from-blue-100 hover:to-blue-200 transition-all transform hover:scale-105 border border-blue-200">
                        <div class="flex items-center gap-3">
                            <svg class="w-10 h-10 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $material->titulo }}</p>
                                <p class="text-sm text-gray-600">PDF - Clique para baixar</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Navegação Entre Aulas --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    @if($anteriorAula)
                    <button 
                        wire:click="navegarParaAula({{ $anteriorAula->id }})"
                        class="flex items-center gap-2 text-gray-700 hover:text-green-600 font-semibold px-6 py-3 rounded-lg border-2 border-gray-300 hover:border-green-500 transition-all transform hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Aula Anterior
                    </button>
                    @else
                    <div></div>
                    @endif

                    @if($proximaAula)
                    <button 
                        wire:click="navegarParaAula({{ $proximaAula->id }})"
                        class="flex items-center gap-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold px-8 py-3 rounded-lg transition-all transform hover:scale-105 shadow-lg">
                        Próxima Aula
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    @else
                    <button 
                        wire:click="voltarParaCursos"
                        class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition-all transform hover:scale-105 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Curso Concluído!
                    </button>
                    @endif
                </div>
            </div>

            {{-- Fórum de Dúvidas --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Dúvidas e Comentários
                </h3>
                
                {{-- Formulário de Novo Comentário --}}
                <div class="mb-6">
                    <textarea 
                        wire:model="novoComentario"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-purple-500 focus:ring-purple-500"
                        rows="3"
                        placeholder="Compartilhe sua dúvida ou comentário sobre esta aula..."></textarea>
                    <div class="mt-2 flex justify-end">
                        <button 
                            wire:click="enviarComentario"
                            class="bg-purple-500 hover:bg-purple-600 text-white font-semibold px-6 py-2 rounded-lg transition-all transform hover:scale-105 shadow-md">
                            Enviar Comentário
                        </button>
                    </div>
                </div>

                {{-- Lista de Comentários --}}
                <div class="space-y-4">
                    @forelse($comentarios as $comentario)
                    <div class="flex gap-3 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <img src="{{ $comentario->foto ? Storage::url($comentario->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($comentario->name) }}" 
                             alt="{{ $comentario->name }}" 
                             class="w-10 h-10 rounded-full border-2 border-purple-200">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-gray-900">{{ $comentario->name }}</span>
                                <span class="text-xs text-gray-500">{{ \\Carbon\\Carbon::parse($comentario->created_at)->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-700">{{ $comentario->comentario }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <p class="text-gray-500 font-medium">Seja o primeiro a comentar!</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar: Lista de Aulas --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-4 sticky top-4">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Conteúdo do Curso
                </h3>
                
                <div class="space-y-2 max-h-[700px] overflow-y-auto custom-scrollbar">
                    @foreach($todasAulas as $moduloId => $aulasDoModulo)
                        @php $moduloInfo = $aulasDoModulo->first(); @endphp
                        <div class="mb-4">
                            <h4 class="font-semibold text-sm text-gray-700 mb-2 px-2 py-1 bg-gray-100 rounded">
                                {{ $moduloInfo->modulo_titulo }}
                            </h4>
                            @foreach($aulasDoModulo as $index => $aulaItem)
                            <button 
                                wire:click="navegarParaAula({{ $aulaItem->id }})"
                                class="w-full text-left p-3 rounded-lg transition-all mb-1 {{ $aulaItem->id == $aula->id ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-900 border-2 border-green-400' : 'hover:bg-gray-50 border border-transparent' }}">
                                <div class="flex items-center gap-2">
                                    @if(isset($progresso[$aulaItem->id]) && $progresso[$aulaItem->id])
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    @else
                                    <div class="w-5 h-5 rounded-full border-2 border-gray-400 flex-shrink-0 flex items-center justify-center">
                                        <span class="text-xs text-gray-600">{{ $index + 1 }}</span>
                                    </div>
                                    @endif
                                    <span class="text-sm flex-1 {{ $aulaItem->id == $aula->id ? 'font-bold' : 'font-medium' }}">
                                        {{ $aulaItem->titulo }}
                                    </span>
                                </div>
                                @if($aulaItem->duracao)
                                <span class="text-xs text-gray-500 ml-7 flex items-center gap-1 mt-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $aulaItem->duracao }}
                                </span>
                                @endif
                            </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos Customizados --}}
    <style>
        .conteudo-aula { line-height: 1.8; }
        .conteudo-aula h2 { font-size: 1.75rem; font-weight: 700; color: #1f2937; margin-top: 2rem; margin-bottom: 1rem; }
        .conteudo-aula h3 { font-size: 1.5rem; font-weight: 600; color: #374151; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        .conteudo-aula p { margin-bottom: 1.25rem; color: #4b5563; }
        .conteudo-aula ul, .conteudo-aula ol { margin-left: 1.5rem; margin-bottom: 1.25rem; }
        .conteudo-aula li { margin-bottom: 0.5rem; }
        .conteudo-aula strong { font-weight: 600; color: #1f2937; }
        .conteudo-aula iframe { margin: 2rem auto; display: block; border-radius: 0.75rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); }
        .conteudo-aula img { margin: 2rem auto; border-radius: 0.75rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }

        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</x-filament-panels::page>
