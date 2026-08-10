@extends('layouts.aluno')

@section('title', $aula->titulo)

@push('styles')
<style>
    /* Animations */
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .slide-in-right {
        animation: slideInRight 0.3s ease-out;
    }

    .fade-in {
        animation: fadeIn 0.3s ease-out;
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Video Player Custom */
    .video-player-wrapper {
        position: relative;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    /* HTML5 Video Element */
    #html5-player {
        background: #000;
        object-fit: contain;
    }

    /* Hide default video controls */
    video::-webkit-media-controls {
        display: none !important;
    }

    video::-webkit-media-controls-enclosure {
        display: none !important;
    }

    /* Custom video controls */
    #video-controls {
        z-index: 20;
        user-select: none;
    }

    /* Center play button animations */
    #center-play-btn {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #video-container:not(.playing) #center-play-btn {
        pointer-events: auto !important;
    }

    #video-container.playing:not(:hover) #video-controls {
        opacity: 0;
        pointer-events: none;
    }

    #video-container.playing:hover #video-controls {
        opacity: 1;
        pointer-events: auto;
    }

    /* Progress Bar Styling */
    #progress-bar {
        -webkit-appearance: none;
        appearance: none;
        outline: none;
        position: relative;
    }

    #progress-bar::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #3b82f6;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.5);
        opacity: 0;
        transition: opacity 0.2s, transform 0.2s;
    }

    #progress-bar:hover::-webkit-slider-thumb,
    #progress-bar:active::-webkit-slider-thumb {
        opacity: 1;
        transform: scale(1.2);
    }

    #progress-bar::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #3b82f6;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.5);
        opacity: 0;
        transition: opacity 0.2s, transform 0.2s;
    }

    #progress-bar:hover::-moz-range-thumb,
    #progress-bar:active::-moz-range-thumb {
        opacity: 1;
        transform: scale(1.2);
    }

    /* Volume Slider */
    #volume-slider {
        -webkit-appearance: none;
        appearance: none;
        outline: none;
    }

    #volume-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    #volume-slider::-moz-range-thumb {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    /* Smooth transitions */
    .video-controls {
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
        transition: opacity 0.3s ease;
    }

    /* Backdrop blur support */
    @supports (backdrop-filter: blur(10px)) or (-webkit-backdrop-filter: blur(10px)) {
        #video-controls {
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        
        #loading-spinner {
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
    }

    /* Tab Navigation */
    .tab-button {
        position: relative;
        transition: all 0.3s ease;
    }

    .tab-button::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: #3b82f6;
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .tab-button.active::after {
        transform: scaleX(1);
    }

    /* Progress Ring */
    .progress-ring {
        transform: rotate(-90deg);
    }

    .progress-ring-circle {
        transition: stroke-dashoffset 0.5s ease;
    }

    /* Interactive Cards */
    .interactive-card {
        transition: all 0.3s ease;
    }

    .interactive-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }

    /* Code Block Styling */
    .code-block {
        background: #1e1e1e;
        border-radius: 8px;
        padding: 1.5rem;
        overflow-x: auto;
        font-family: 'Fira Code', 'Monaco', 'Courier New', monospace;
    }

    /* Quiz Styling */
    .quiz-option {
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .quiz-option:hover {
        background: #f3f4f6;
        transform: translateX(4px);
    }

    .quiz-option.selected {
        background: #eff6ff;
        border-color: #3b82f6;
    }

    .quiz-option.correct {
        background: #d1fae5;
        border-color: #10b981;
    }

    .quiz-option.incorrect {
        background: #fee2e2;
        border-color: #ef4444;
    }

    /* Notes Editor */
    .notes-editor {
        min-height: 200px;
        transition: all 0.3s ease;
    }

    .notes-editor:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Loading States */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s ease-in-out infinite;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Achievement Badge */
    .achievement-badge {
        animation: pulse 2s ease-in-out infinite;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-50 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Top Navigation -->
            <div class="flex items-center justify-between py-4">
                <!-- Breadcrumb -->
                <nav class="flex items-center space-x-2 text-sm">
                    <a href="{{ route('aluno.cursos') }}" class="flex items-center text-gray-600 dark:text-gray-400 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </a>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    <a href="{{ route('aluno.curso.show', $curso->id) }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 transition truncate max-w-xs">
                        {{ \Illuminate\Support\Str::limit($curso->titulo, 30) }}
                    </a>
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-gray-900 dark:text-white font-medium truncate max-w-xs">{{ $aula->titulo }}</span>
                </nav>

                <!-- Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Progress Circle -->
                    <div class="relative w-16 h-16">
                        <svg class="w-16 h-16 progress-ring">
                            <circle class="text-gray-200 dark:text-gray-700" stroke-width="4" stroke="currentColor" fill="transparent" r="28" cx="32" cy="32" />
                            <circle class="progress-ring-circle text-blue-600" stroke-width="4" stroke-dasharray="175.93" stroke-dashoffset="{{ 175.93 - (175.93 * ($progressoCurso / 100)) }}" stroke-linecap="round" stroke="currentColor" fill="transparent" r="28" cx="32" cy="32" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $progressoCurso }}%</span>
                        </div>
                    </div>

                    <!-- Complete Button -->
                    @if(!$progresso->concluida)
                        <button onclick="marcarConcluida()" id="btn-concluir" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl transition-all duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Marcar como Concluída
                        </button>
                    @else
                        <div class="px-6 py-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-2 border-green-500 text-green-700 dark:text-green-300 font-semibold rounded-xl flex items-center shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Aula Concluída ✨
                        </div>
                    @endif

                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" class="p-3 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="pb-4">
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $aula->titulo }}</h1>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $aulasConcluidas }}/{{ $totalAulas }} aulas</span>
                </div>
                <div class="relative">
                    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div id="barra-progresso-aula" class="h-2 bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-600 rounded-full transition-all duration-500" style="width: {{ $progresso->progresso_porcentagem ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Video Player -->
                <div class="video-player-wrapper" id="custom-video-wrapper">
                    @if($aula->tipo_conteudo === 'video' && $aula->video_url)
                        @php
                            // Detectar tipo de vídeo
                            $videoType = 'direct'; // direct, youtube, vimeo
                            $videoSource = $aula->video_url;
                            
                            // Verificar se é YouTube
                            if (strpos($videoSource, 'youtube.com') !== false || strpos($videoSource, 'youtu.be') !== false) {
                                $videoType = 'youtube';
                                // Extrair ID do YouTube
                                if (strpos($videoSource, 'youtube.com/watch') !== false) {
                                    preg_match('/[?&]v=([^&]+)/', $videoSource, $matches);
                                    $videoId = $matches[1] ?? '';
                                } elseif (strpos($videoSource, 'youtu.be/') !== false) {
                                    $videoId = substr(parse_url($videoSource, PHP_URL_PATH), 1);
                                } elseif (strpos($videoSource, 'youtube.com/embed/') !== false) {
                                    preg_match('/embed\/([^?]+)/', $videoSource, $matches);
                                    $videoId = $matches[1] ?? '';
                                }
                                // Converter para link direto (não funciona para privados, mas serve de fallback)
                                $videoSource = "https://www.youtube.com/embed/{$videoId}?autoplay=0&controls=1&rel=0&modestbranding=1";
                            }
                            // Verificar se é Vimeo
                            elseif (strpos($videoSource, 'vimeo.com') !== false) {
                                $videoType = 'vimeo';
                            }
                        @endphp
                        
                        <div id="video-container" class="relative aspect-video bg-black group" data-video-type="{{ $videoType }}">
                            <!-- HTML5 Video Player (para arquivos diretos MP4, WebM, etc) -->
                            @if($videoType === 'direct')
                                <video id="html5-player" class="w-full h-full" preload="metadata">
                                    <source src="{{ $videoSource }}" type="video/mp4">
                                    <source src="{{ $videoSource }}" type="video/webm">
                                    <source src="{{ $videoSource }}" type="video/ogg">
                                    Seu navegador não suporta reprodução de vídeo HTML5.
                                </video>
                            @elseif($videoType === 'youtube')
                                <!-- Para YouTube, usar iframe (melhor compatibilidade) -->
                                <iframe id="youtube-frame" src="{{ $videoSource }}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            @elseif($videoType === 'vimeo')
                                <iframe id="vimeo-frame" src="{{ $videoSource }}" class="w-full h-full" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                            @endif
                            
                            <!-- Custom Controls Overlay -->
                            <div id="video-controls" class="absolute inset-0 flex flex-col justify-between p-6 bg-gradient-to-t from-black/90 via-transparent to-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none group-hover:pointer-events-auto">
                                <!-- Top Bar -->
                                <div class="flex items-center justify-between">
                                    <div class="text-white font-semibold text-lg drop-shadow-lg">{{ $aula->titulo }}</div>
                                    @if($videoType === 'direct')
                                    <button onclick="togglePictureInPicture()" class="p-2 hover:bg-white/20 rounded-full transition text-white backdrop-blur-sm" title="Picture in Picture">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h4m10 0h4M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                        </svg>
                                    </button>
                                    @endif
                                </div>

                                <!-- Center Play Button -->
                                <div class="flex items-center justify-center pointer-events-auto">
                                    <button id="center-play-btn" onclick="togglePlay()" class="p-8 bg-blue-600/90 hover:bg-blue-700 rounded-full transition-all transform hover:scale-110 active:scale-95 text-white shadow-2xl backdrop-blur-sm opacity-0 group-hover:opacity-100">
                                        <svg id="center-play-icon" class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Bottom Controls -->
                                <div class="space-y-3 pointer-events-auto">
                                    <!-- Progress Bar -->
                                    <div class="relative group/progress">
                                        <input type="range" id="progress-bar" min="0" max="100" value="0" step="0.1" class="w-full h-1 bg-white/30 rounded-lg appearance-none cursor-pointer group-hover/progress:h-2 transition-all backdrop-blur-sm" style="background: linear-gradient(to right, #3b82f6 0%, #3b82f6 0%, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0.3) 100%);">
                                        <div id="progress-tooltip" class="absolute bottom-8 left-0 hidden px-3 py-1.5 bg-black/95 text-white text-xs rounded-lg transform -translate-x-1/2 backdrop-blur-sm shadow-lg">
                                            <div class="font-semibold">0:00</div>
                                            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-black/95"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Control Buttons -->
                                    <div class="flex items-center justify-between text-white">
                                        <div class="flex items-center space-x-3 md:space-x-4">
                                            <!-- Play/Pause -->
                                            <button onclick="togglePlay()" class="p-2 hover:bg-white/20 rounded-full transition-all active:scale-95 backdrop-blur-sm">
                                                <svg id="play-icon" class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                                                </svg>
                                            </button>
                                            
                                            <!-- Skip Back -->
                                            <button onclick="skipBackward()" class="p-2 hover:bg-white/20 rounded-full transition-all active:scale-95 backdrop-blur-sm hidden md:block" title="Voltar 10s">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                                                </svg>
                                            </button>
                                            
                                            <!-- Skip Forward -->
                                            <button onclick="skipForward()" class="p-2 hover:bg-white/20 rounded-full transition-all active:scale-95 backdrop-blur-sm hidden md:block" title="Avançar 10s">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" />
                                                </svg>
                                            </button>
                                            
                                            <!-- Volume -->
                                            <div class="hidden md:flex items-center space-x-2 group/volume">
                                                <button onclick="toggleMute()" class="p-2 hover:bg-white/20 rounded-full transition-all active:scale-95 backdrop-blur-sm">
                                                    <svg id="volume-icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <input type="range" id="volume-slider" min="0" max="100" value="100" class="w-0 group-hover/volume:w-20 h-1 bg-white/30 rounded-lg appearance-none cursor-pointer transition-all backdrop-blur-sm">
                                            </div>
                                            
                                            <!-- Time Display -->
                                            <span class="text-sm font-medium drop-shadow-lg" id="video-time">0:00 / 0:00</span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2 md:space-x-4">
                                            <!-- Speed Control -->
                                            <div class="relative group/speed">
                                                <button onclick="toggleSpeedMenu()" class="px-3 py-1.5 text-sm hover:bg-white/20 rounded-lg transition-all backdrop-blur-sm font-semibold">
                                                    <span id="speed-text">1x</span>
                                                </button>
                                                <div id="speed-menu" class="hidden absolute bottom-full right-0 mb-2 bg-black/95 rounded-lg overflow-hidden shadow-2xl backdrop-blur-sm">
                                                    <button onclick="setSpeed(0.5)" class="block w-full px-5 py-2.5 text-sm hover:bg-white/20 transition text-left whitespace-nowrap">0.5x</button>
                                                    <button onclick="setSpeed(0.75)" class="block w-full px-5 py-2.5 text-sm hover:bg-white/20 transition text-left">0.75x</button>
                                                    <button onclick="setSpeed(1)" class="block w-full px-5 py-2.5 text-sm hover:bg-white/20 transition text-left bg-white/10">1x (Normal)</button>
                                                    <button onclick="setSpeed(1.25)" class="block w-full px-5 py-2.5 text-sm hover:bg-white/20 transition text-left">1.25x</button>
                                                    <button onclick="setSpeed(1.5)" class="block w-full px-5 py-2.5 text-sm hover:bg-white/20 transition text-left">1.5x</button>
                                                    <button onclick="setSpeed(2)" class="block w-full px-5 py-2.5 text-sm hover:bg-white/20 transition text-left">2x</button>
                                                </div>
                                            </div>
                                            
                                            <!-- Fullscreen -->
                                            <button onclick="toggleFullscreen()" class="p-2 hover:bg-white/20 rounded-full transition-all active:scale-95 backdrop-blur-sm">
                                                <svg id="fullscreen-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Loading Spinner -->
                            <div id="loading-spinner" class="absolute inset-0 flex items-center justify-center bg-black/60 backdrop-blur-sm hidden pointer-events-none">
                                <div class="relative">
                                    <div class="animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-blue-500"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Paused Overlay -->
                            <div id="paused-overlay" class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-[2px] hidden pointer-events-none">
                                <div class="text-white text-center">
                                    <svg class="w-24 h-24 mx-auto mb-4 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                                    </svg>
                                    <p class="text-lg font-semibold">Pausado</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Tab Navigation -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex -mb-px">
                            <button onclick="switchTab('overview')" class="tab-button active flex-1 py-4 px-6 text-center font-semibold text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition" data-tab="overview">
                                <div class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Visão Geral
                                </div>
                            </button>
                            <button onclick="switchTab('content')" class="tab-button flex-1 py-4 px-6 text-center font-semibold text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition" data-tab="content">
                                <div class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Conteúdo
                                </div>
                            </button>
                            <button onclick="switchTab('quiz')" class="tab-button flex-1 py-4 px-6 text-center font-semibold text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition" data-tab="quiz">
                                <div class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Quiz
                                </div>
                            </button>
                            <button onclick="switchTab('notes')" class="tab-button flex-1 py-4 px-6 text-center font-semibold text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition" data-tab="notes">
                                <div class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Notas
                                </div>
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <!-- Overview Tab -->
                        <div id="tab-overview" class="tab-content">
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Sobre esta aula</h3>
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $aula->descricao }}</p>
                                </div>

                                @if($aula->objetivos)
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">🎯 Objetivos de Aprendizagem</h4>
                                    <ul class="space-y-2">
                                        @foreach(json_decode($aula->objetivos) as $objetivo)
                                        <li class="flex items-start">
                                            <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-gray-700 dark:text-gray-300">{{ $objetivo }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                <!-- Stats Cards -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                                    <div class="interactive-card bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-4 rounded-xl border border-blue-200 dark:border-blue-700">
                                        <div class="text-3xl mb-2">⏱️</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Duração</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $aula->duracao_minutos }} min</div>
                                    </div>
                                    <div class="interactive-card bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 p-4 rounded-xl border border-green-200 dark:border-green-700">
                                        <div class="text-3xl mb-2">📊</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Nível</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white capitalize">{{ $aula->nivel ?? 'Intermediário' }}</div>
                                    </div>
                                    <div class="interactive-card bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 p-4 rounded-xl border border-purple-200 dark:border-purple-700">
                                        <div class="text-3xl mb-2">👥</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Alunos</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $curso->matriculas_count ?? 0 }}</div>
                                    </div>
                                    <div class="interactive-card bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 p-4 rounded-xl border border-orange-200 dark:border-orange-700">
                                        <div class="text-3xl mb-2">⭐</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">Avaliação</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $curso->avaliacao_media ?? '4.8' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Tab -->
                        <div id="tab-content" class="tab-content hidden">
                            <div class="prose dark:prose-invert max-w-none">
                                {!! $aula->conteudo_texto !!}
                            </div>

                            @if($aula->materiais_complementares)
                            <div class="mt-8">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📚 Materiais Complementares</h4>
                                <div class="space-y-3">
                                    @foreach(json_decode($aula->materiais_complementares) as $material)
                                    <a href="{{ $material->url }}" target="_blank" class="interactive-card flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400">
                                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg mr-4">
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $material->titulo }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $material->tipo }}</div>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Quiz Tab -->
                        <div id="tab-quiz" class="tab-content hidden">
                            <div class="space-y-6">
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-6 rounded-xl border border-blue-200 dark:border-blue-700">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">📝 Teste seus conhecimentos</h3>
                                    <p class="text-gray-700 dark:text-gray-300">Complete o quiz para verificar seu aprendizado desta aula.</p>
                                </div>

                                @if(isset($aula->quiz) && count($aula->quiz) > 0)
                                    <div id="quiz-container" class="space-y-6">
                                        @foreach($aula->quiz as $index => $pergunta)
                                        <div class="quiz-question bg-white dark:bg-gray-700/50 p-6 rounded-xl border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-start mb-4">
                                                <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">
                                                    {{ $index + 1 }}
                                                </div>
                                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $pergunta->pergunta }}</h4>
                                            </div>
                                            <div class="space-y-3 ml-11">
                                                @foreach($pergunta->opcoes as $opcaoIndex => $opcao)
                                                <div class="quiz-option border-2 border-gray-200 dark:border-gray-600 rounded-lg p-4 cursor-pointer" onclick="selectOption({{ $index }}, {{ $opcaoIndex }}, {{ $pergunta->resposta_correta }})">
                                                    <div class="flex items-center">
                                                        <div class="w-6 h-6 rounded-full border-2 border-gray-400 dark:border-gray-500 mr-3 flex items-center justify-center">
                                                            <div class="w-3 h-3 rounded-full bg-blue-600 hidden option-check"></div>
                                                        </div>
                                                        <span class="text-gray-700 dark:text-gray-300">{{ $opcao }}</span>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                            <div class="quiz-feedback hidden ml-11 mt-4 p-4 rounded-lg"></div>
                                        </div>
                                        @endforeach
                                    </div>

                                    <div class="flex justify-between items-center mt-6">
                                        <div id="quiz-score" class="text-lg font-semibold text-gray-900 dark:text-white"></div>
                                        <button onclick="submitQuiz()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                                            Enviar Respostas
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-lg">Nenhum quiz disponível para esta aula</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Notes Tab -->
                        <div id="tab-notes" class="tab-content hidden">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">✍️ Minhas Anotações</h3>
                                    <button onclick="saveNotes()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                        </svg>
                                        Salvar
                                    </button>
                                </div>

                                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 mb-4">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                            <strong>Dica:</strong> Suas anotações são salvas automaticamente e podem ser acessadas a qualquer momento.
                                        </p>
                                    </div>
                                </div>

                                <textarea id="notes-editor" class="notes-editor w-full p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-0 custom-scrollbar resize-none" rows="12" placeholder="Escreva suas anotações aqui...">{{ $progresso->notas ?? '' }}</textarea>

                                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span id="notes-timestamp">Última atualização: {{ $progresso->updated_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span id="notes-char-count">0 caracteres</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Course Progress Card -->
                <div class="interactive-card bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📈 Seu Progresso</h3>
                    
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Conclusão do Curso</span>
                            <span class="text-2xl font-bold text-blue-600">{{ $progressoCurso }}%</span>
                        </div>
                        <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500" style="width: {{ $progressoCurso }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-700">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $aulasConcluidas }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Concluídas</div>
                        </div>
                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-700">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totalAulas - $aulasConcluidas }}</div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">Restantes</div>
                        </div>
                    </div>

                    @if($progresso->tempo_estudo_minutos > 0)
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Tempo de estudo: <strong class="text-gray-900 dark:text-white">{{ $progresso->tempo_estudo_minutos }} min</strong></span>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Lesson Navigation -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Conteúdo do Curso
                        </h3>
                    </div>

                    <div class="p-4 max-h-96 overflow-y-auto custom-scrollbar">
                        @foreach($modulos as $modulo)
                        <div class="mb-4">
                            <div class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                {{ $modulo->titulo }}
                            </div>
                            <div class="space-y-1">
                                @foreach($modulo->aulas as $aulaItem)
                                <a href="{{ route('aluno.aula', $aulaItem->id) }}" class="block p-3 rounded-lg transition-all {{ $aulaItem->id == $aula->id ? 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-600' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center flex-1 min-w-0">
                                            @if($aulaItem->progresso && $aulaItem->progresso->concluida)
                                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <div class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 mr-2 flex-shrink-0"></div>
                                            @endif
                                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate {{ $aulaItem->id == $aula->id ? 'font-semibold' : '' }}">
                                                {{ $aulaItem->titulo }}
                                            </span>
                                        </div>
                                        @if($aulaItem->duracao_minutos)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2 flex-shrink-0">{{ $aulaItem->duracao_minutos }}min</span>
                                        @endif
                                    </div>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Next Lesson -->
                @if($proximaAula)
                <div class="interactive-card bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-2xl p-6 border-2 border-blue-200 dark:border-blue-700">
                    <div class="flex items-center mb-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Próxima Aula</h4>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ $proximaAula->titulo }}</h3>
                    <a href="{{ route('aluno.aula', $proximaAula->id) }}" class="block w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-center font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        Continuar Aprendendo →
                    </a>
                </div>
                @endif

                <!-- Achievement Card -->
                @if($progresso->concluida)
                <div class="achievement-badge interactive-card bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/30 dark:to-orange-900/30 rounded-2xl p-6 border-2 border-yellow-300 dark:border-yellow-700">
                    <div class="text-center">
                        <div class="text-6xl mb-3">🏆</div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Parabéns!</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Você concluiu esta aula com sucesso!</p>
                    </div>
                </div>
                @endif

                <!-- Help Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Precisa de Ajuda?
                    </h4>
                    <div class="space-y-3">
                        <a href="#" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span class="text-sm">Fórum de Discussão</span>
                        </a>
                        <a href="#" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm">Contatar Instrutor</span>
                        </a>
                        <a href="#" class="flex items-center text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm">Central de Ajuda</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Video Player Instance
let videoPlayer = null;
let isVideoReady = false;
let progressUpdateInterval;
let hideControlsTimeout;

// Initialize Video Player
document.addEventListener('DOMContentLoaded', function() {
    const videoContainer = document.getElementById('video-container');
    if (!videoContainer) return;
    
    const videoType = videoContainer.dataset.videoType;
    
    if (videoType === 'direct') {
        // HTML5 Video
        videoPlayer = document.getElementById('html5-player');
        if (videoPlayer) {
            setupHTML5Player();
        }
    } else if (videoType === 'youtube' || videoType === 'vimeo') {
        // For iframes, we use their native controls
        console.log('Using iframe player for:', videoType);
        // Hide custom controls for iframe players
        document.getElementById('video-controls').style.display = 'none';
    }
});

function setupHTML5Player() {
    isVideoReady = true;
    
    // Event listeners
    videoPlayer.addEventListener('loadedmetadata', function() {
        updateTimeDisplay();
        console.log('Video loaded:', {
            duration: videoPlayer.duration,
            width: videoPlayer.videoWidth,
            height: videoPlayer.videoHeight
        });
    });
    
    videoPlayer.addEventListener('timeupdate', updateProgress);
    videoPlayer.addEventListener('play', onVideoPlay);
    videoPlayer.addEventListener('pause', onVideoPause);
    videoPlayer.addEventListener('ended', onVideoEnded);
    videoPlayer.addEventListener('waiting', () => showLoading(true));
    videoPlayer.addEventListener('canplay', () => showLoading(false));
    videoPlayer.addEventListener('loadstart', () => showLoading(true));
    videoPlayer.addEventListener('loadeddata', () => showLoading(false));
    
    // Volume from localStorage
    const savedVolume = localStorage.getItem('video-volume');
    if (savedVolume) {
        videoPlayer.volume = parseFloat(savedVolume);
        document.getElementById('volume-slider').value = savedVolume * 100;
    }
    
    console.log('HTML5 Player initialized!');
}

function onVideoPlay() {
    const playIcon = document.getElementById('play-icon');
    const centerPlayIcon = document.getElementById('center-play-icon');
    const container = document.getElementById('video-container');
    const pausedOverlay = document.getElementById('paused-overlay');
    
    // Update icons to pause
    const pauseIconSVG = '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />';
    playIcon.innerHTML = pauseIconSVG;
    centerPlayIcon.innerHTML = pauseIconSVG;
    
    container.classList.add('playing');
    pausedOverlay.classList.add('hidden');
    showLoading(false);
    
    // Auto-hide controls
    startAutoHide();
}

function onVideoPause() {
    const playIcon = document.getElementById('play-icon');
    const centerPlayIcon = document.getElementById('center-play-icon');
    const container = document.getElementById('video-container');
    const pausedOverlay = document.getElementById('paused-overlay');
    
    // Update icons to play
    const playIconSVG = '<path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />';
    playIcon.innerHTML = playIconSVG;
    centerPlayIcon.innerHTML = playIconSVG;
    
    container.classList.remove('playing');
    pausedOverlay.classList.remove('hidden');
    
    clearTimeout(hideControlsTimeout);
}

function onVideoEnded() {
    const playIcon = document.getElementById('play-icon');
    const centerPlayIcon = document.getElementById('center-play-icon');
    const container = document.getElementById('video-container');
    
    const playIconSVG = '<path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />';
    playIcon.innerHTML = playIconSVG;
    centerPlayIcon.innerHTML = playIconSVG;
    
    container.classList.remove('playing');
}

function showLoading(show) {
    const loadingSpinner = document.getElementById('loading-spinner');
    if (show) {
        loadingSpinner.classList.remove('hidden');
    } else {
        loadingSpinner.classList.add('hidden');
    }
}

function startAutoHide() {
    clearTimeout(hideControlsTimeout);
    hideControlsTimeout = setTimeout(() => {
        const container = document.getElementById('video-container');
        const controls = document.getElementById('video-controls');
        if (container.classList.contains('playing')) {
            controls.style.opacity = '0';
            controls.style.pointerEvents = 'none';
        }
    }, 3000);
}

// Video Controls
function togglePlay() {
    if (!isVideoReady || !videoPlayer) return;
    
    if (videoPlayer.paused) {
        videoPlayer.play();
    } else {
        videoPlayer.pause();
    }
}

function skipBackward() {
    if (!isVideoReady || !videoPlayer) return;
    videoPlayer.currentTime = Math.max(0, videoPlayer.currentTime - 10);
}

function skipForward() {
    if (!isVideoReady || !videoPlayer) return;
    videoPlayer.currentTime = Math.min(videoPlayer.duration, videoPlayer.currentTime + 10);
}

function toggleMute() {
    if (!isVideoReady || !videoPlayer) return;
    
    const volumeIcon = document.getElementById('volume-icon');
    const volumeSlider = document.getElementById('volume-slider');
    
    if (videoPlayer.muted) {
        videoPlayer.muted = false;
        volumeIcon.innerHTML = '<path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />';
    } else {
        videoPlayer.muted = true;
        volumeIcon.innerHTML = '<path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />';
    }
}

function toggleSpeedMenu() {
    const speedMenu = document.getElementById('speed-menu');
    speedMenu.classList.toggle('hidden');
}

function setSpeed(speed) {
    if (!isVideoReady || !videoPlayer) return;
    
    videoPlayer.playbackRate = speed;
    document.getElementById('speed-text').textContent = speed + 'x';
    document.getElementById('speed-menu').classList.add('hidden');
    
    // Update active state
    document.querySelectorAll('#speed-menu button').forEach(btn => {
        btn.classList.remove('bg-white/10');
        if (btn.textContent.trim().startsWith(speed.toString())) {
            btn.classList.add('bg-white/10');
        }
    });
}

function toggleFullscreen() {
    const videoWrapper = document.getElementById('custom-video-wrapper');
    const fullscreenIcon = document.getElementById('fullscreen-icon');
    
    if (!document.fullscreenElement) {
        videoWrapper.requestFullscreen().then(() => {
            fullscreenIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
        }).catch(err => {
            console.error('Fullscreen error:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            fullscreenIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />';
        });
    }
}

async function togglePictureInPicture() {
    if (!isVideoReady || !videoPlayer) return;
    
    try {
        if (document.pictureInPictureElement) {
            await document.exitPictureInPicture();
        } else {
            await videoPlayer.requestPictureInPicture();
        }
    } catch (error) {
        console.error('PiP error:', error);
    }
}

// Progress Bar
const progressBar = document.getElementById('progress-bar');
const progressTooltip = document.getElementById('progress-tooltip');

if (progressBar) {
    progressBar.addEventListener('input', function() {
        if (!isVideoReady || !videoPlayer) return;
        
        const seekTime = (this.value / 100) * videoPlayer.duration;
        videoPlayer.currentTime = seekTime;
    });
    
    progressBar.addEventListener('mousemove', function(e) {
        if (!videoPlayer) return;
        
        const rect = this.getBoundingClientRect();
        const offsetX = e.clientX - rect.left;
        const percentage = (offsetX / rect.width) * 100;
        const time = (percentage / 100) * videoPlayer.duration;
        
        progressTooltip.textContent = formatTime(time);
        progressTooltip.style.left = offsetX + 'px';
        progressTooltip.classList.remove('hidden');
    });
    
    progressBar.addEventListener('mouseleave', function() {
        progressTooltip.classList.add('hidden');
    });
}

// Volume Slider
const volumeSlider = document.getElementById('volume-slider');
if (volumeSlider) {
    volumeSlider.addEventListener('input', function() {
        if (!isVideoReady || !videoPlayer) return;
        
        const volume = this.value / 100;
        videoPlayer.volume = volume;
        localStorage.setItem('video-volume', volume);
        
        if (volume === 0) {
            videoPlayer.muted = true;
        } else if (videoPlayer.muted) {
            videoPlayer.muted = false;
        }
        
        // Update icon
        const volumeIcon = document.getElementById('volume-icon');
        if (volume === 0) {
            volumeIcon.innerHTML = '<path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />';
        } else {
            volumeIcon.innerHTML = '<path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />';
        }
    });
}

// Update Progress
function updateProgress() {
    if (!isVideoReady || !videoPlayer) return;
    
    try {
        const currentTime = videoPlayer.currentTime;
        const duration = videoPlayer.duration;
        
        if (duration > 0 && !isNaN(duration)) {
            const percentage = (currentTime / duration) * 100;
            progressBar.value = percentage;
            progressBar.style.background = `linear-gradient(to right, #3b82f6 0%, #3b82f6 ${percentage}%, rgba(255,255,255,0.3) ${percentage}%, rgba(255,255,255,0.3) 100%)`;
            
            updateTimeDisplay();
        }
    } catch (error) {
        // Video not ready yet
    }
}

function updateTimeDisplay() {
    if (!isVideoReady || !videoPlayer) return;
    
    try {
        const currentTime = videoPlayer.currentTime;
        const duration = videoPlayer.duration;
        
        document.getElementById('video-time').textContent = 
            formatTime(currentTime) + ' / ' + formatTime(duration);
    } catch (error) {
        // Video not ready yet
    }
}

function formatTime(seconds) {
    if (!seconds || isNaN(seconds) || !isFinite(seconds)) return '0:00';
    
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    if (hrs > 0) {
        return hrs + ':' + (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
    }
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}

// Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    if (!isVideoReady || !videoPlayer) return;
    
    // Don't trigger if user is typing in input/textarea
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    
    switch(e.key) {
        case ' ':
        case 'k':
            e.preventDefault();
            togglePlay();
            break;
        case 'ArrowLeft':
            e.preventDefault();
            skipBackward();
            break;
        case 'ArrowRight':
            e.preventDefault();
            skipForward();
            break;
        case 'f':
            e.preventDefault();
            toggleFullscreen();
            break;
        case 'm':
            e.preventDefault();
            toggleMute();
            break;
        case 'ArrowUp':
            e.preventDefault();
            if (videoPlayer) {
                const newVolume = Math.min(1, videoPlayer.volume + 0.1);
                videoPlayer.volume = newVolume;
                volumeSlider.value = newVolume * 100;
                localStorage.setItem('video-volume', newVolume);
            }
            break;
        case 'ArrowDown':
            e.preventDefault();
            if (videoPlayer) {
                const newVolume = Math.max(0, videoPlayer.volume - 0.1);
                videoPlayer.volume = newVolume;
                volumeSlider.value = newVolume * 100;
                localStorage.setItem('video-volume', newVolume);
            }
            break;
    }
});

// Click on video to play/pause
document.getElementById('video-container')?.addEventListener('click', function(e) {
    // Don't toggle if clicking on controls
    if (e.target.closest('#video-controls')) return;
    if (e.target.closest('button')) return;
    if (e.target.closest('input')) return;
    
    togglePlay();
});

// Mouse movement shows controls
document.getElementById('video-container')?.addEventListener('mousemove', function() {
    const controls = document.getElementById('video-controls');
    if (controls && this.classList.contains('playing')) {
        controls.style.opacity = '1';
        controls.style.pointerEvents = 'auto';
        startAutoHide();
    }
});

// Tab Switching
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Add active class to clicked button
    document.querySelector('[data-tab="' + tabName + '"]').classList.add('active');
}

// Mark as Complete
function marcarConcluida() {
    if(confirm('Deseja marcar esta aula como concluída?')) {
        fetch('{{ route("aluno.aula.concluir", $aula->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Show success animation
                const btn = document.getElementById('btn-concluir');
                btn.innerHTML = `
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Aula Concluída ✨
                `;
                btn.className = 'px-6 py-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border-2 border-green-500 text-green-700 dark:text-green-300 font-semibold rounded-xl flex items-center shadow-md';
                
                // Update progress bar
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        });
    }
}

// Quiz Functions
let quizAnswers = {};

function selectOption(questionIndex, optionIndex, correctAnswer) {
    quizAnswers[questionIndex] = optionIndex;
    
    // Update UI
    const question = document.querySelectorAll('.quiz-question')[questionIndex];
    const options = question.querySelectorAll('.quiz-option');
    
    options.forEach((option, idx) => {
        option.classList.remove('selected');
        option.querySelector('.option-check').classList.add('hidden');
    });
    
    options[optionIndex].classList.add('selected');
    options[optionIndex].querySelector('.option-check').classList.remove('hidden');
}

function submitQuiz() {
    const questions = document.querySelectorAll('.quiz-question');
    let correct = 0;
    let total = questions.length;
    
    questions.forEach((question, index) => {
        const options = question.querySelectorAll('.quiz-option');
        const feedback = question.querySelector('.quiz-feedback');
        const correctAnswer = parseInt(options[0].getAttribute('onclick').match(/\d+,\s*(\d+)/)[1]);
        const userAnswer = quizAnswers[index];
        
        if (userAnswer === correctAnswer) {
            correct++;
            options[userAnswer].classList.add('correct');
            feedback.className = 'quiz-feedback ml-11 mt-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300';
            feedback.innerHTML = '✅ Correto! Excelente trabalho!';
        } else {
            options[userAnswer].classList.add('incorrect');
            options[correctAnswer].classList.add('correct');
            feedback.className = 'quiz-feedback ml-11 mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300';
            feedback.innerHTML = '❌ Incorreto. A resposta correta está destacada em verde.';
        }
        
        feedback.classList.remove('hidden');
    });
    
    const scoreElement = document.getElementById('quiz-score');
    const percentage = Math.round((correct / total) * 100);
    scoreElement.innerHTML = `Sua pontuação: <span class="text-blue-600">${correct}/${total}</span> (${percentage}%)`;
    
    // Save quiz results
    fetch('{{ route("aluno.aula.quiz", $aula->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            score: percentage,
            answers: quizAnswers
        })
    });
}

// Notes Functions
const notesEditor = document.getElementById('notes-editor');
const charCount = document.getElementById('notes-char-count');

if (notesEditor) {
    notesEditor.addEventListener('input', function() {
        charCount.textContent = this.value.length + ' caracteres';
    });
    
    // Auto-save notes
    let saveTimeout;
    notesEditor.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(saveNotes, 2000);
    });
}

function saveNotes() {
    const notes = notesEditor.value;
    
    fetch('{{ route("aluno.aula.notas", $aula->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ notas: notes })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('notes-timestamp').textContent = 'Última atualização: agora mesmo';
            
            // Show save indicator
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Salvo!
            `;
            
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
            }, 2000);
        }
    });
}

// Theme Toggle
function toggleTheme() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
}

// Initialize theme
if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark');
}

// Track study time
let studyStartTime = Date.now();

window.addEventListener('beforeunload', function() {
    const studyTime = Math.round((Date.now() - studyStartTime) / 60000); // minutes
    
    if (studyTime > 0) {
        navigator.sendBeacon('{{ route("aluno.aula.tempo", $aula->id) }}', JSON.stringify({
            tempo: studyTime,
            _token: '{{ csrf_token() }}'
        }));
    }
});

// Update progress periodically
setInterval(function() {
    const progressBar = document.getElementById('barra-progresso-aula');
    const currentProgress = parseInt(progressBar.style.width);
    
    if (currentProgress < 100) {
        const newProgress = Math.min(currentProgress + 5, 100);
        progressBar.style.width = newProgress + '%';
        
        // Update server
        fetch('{{ route("aluno.aula.progresso", $aula->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ progresso: newProgress })
        });
    }
}, 30000); // Every 30 seconds
</script>
@endpush
@endsection