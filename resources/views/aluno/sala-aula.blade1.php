<x-desktop-layout>
    <x-slot name="pageTitle">{{ $aula->titulo ?? 'Sala de Aula' }}</x-slot>
    <x-slot name="pageSubtitle">{{ $curso->titulo ?? 'Curso' }} • {{ $modulo->titulo ?? 'Módulo' }}</x-slot>
    
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Coluna Principal: Player e Conteúdo --}}
        <div class="lg:col-span-3 space-y-6">
            {{-- Player de Vídeo --}}
            <div class="bg-slate-900 rounded-2xl overflow-hidden shadow-2xl aspect-video relative group">
                {{-- Video Player Placeholder --}}
                <div class="absolute inset-0 flex items-center justify-center">
                    <button class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center hover:bg-white/30 transition-all group-hover:scale-110">
                        <i class="fa-solid fa-play text-white text-2xl ml-1"></i>
                    </button>
                </div>
                
                {{-- Video Controls Overlay --}}
                <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="flex items-center space-x-4">
                        <button class="text-white hover:text-blue-400 transition-colors">
                            <i class="fa-solid fa-play text-xl"></i>
                        </button>
                        <div class="flex-1 h-1 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 w-1/3"></div>
                        </div>
                        <span class="text-white text-sm font-bold">12:30 / 35:00</span>
                        <button class="text-white hover:text-blue-400 transition-colors">
                            <i class="fa-solid fa-volume-high"></i>
                        </button>
                        <button class="text-white hover:text-blue-400 transition-colors">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            {{-- Informações da Aula --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h1 class="text-2xl font-black text-slate-800 mb-2">{{ $aula->titulo ?? 'Título da Aula' }}</h1>
                        <div class="flex items-center space-x-4 text-sm text-slate-500">
                            <span class="flex items-center">
                                <i class="fa-solid fa-user-tie mr-2"></i>
                                {{ $curso->instrutor ?? 'Professor' }}
                            </span>
                            <span class="flex items-center">
                                <i class="fa-regular fa-clock mr-2"></i>
                                {{ $aula->duracao ?? '35' }} min
                            </span>
                            <span class="flex items-center">
                                <i class="fa-regular fa-eye mr-2"></i>
                                {{ $aula->visualizacoes ?? '245' }} visualizações
                            </span>
                        </div>
                    </div>
                    
                    <button class="px-6 py-3 bg-green-500 text-white rounded-xl font-black text-sm hover:bg-green-600 transition-colors shadow-md flex items-center space-x-2">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Marcar como Concluída</span>
                    </button>
                </div>
                
                {{-- Navegação entre Aulas --}}
                <div class="flex items-center space-x-3 pt-4 border-t border-slate-200">
                    @if($anteriorAula ?? false)
                    <a href="{{ route('aluno.aula', $anteriorAula->id) }}" class="flex-1 flex items-center justify-center space-x-2 px-4 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold text-sm hover:bg-slate-200 transition-colors">
                        <i class="fa-solid fa-chevron-left"></i>
                        <span>Aula Anterior</span>
                    </a>
                    @endif
                    
                    @if($proximaAula ?? false)
                    <a href="{{ route('aluno.aula', $proximaAula->id) }}" class="flex-1 flex items-center justify-center space-x-2 px-4 py-3 bg-[#003366] text-white rounded-xl font-bold text-sm hover:bg-[#002244] transition-colors">
                        <span>Próxima Aula</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    @endif
                </div>
            </div>
            
            {{-- Tabs: Descrição, Materiais, Comentários --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ activeTab: 'descricao' }">
                {{-- Tab Headers --}}
                <div class="flex items-center border-b border-slate-200">
                    <button 
                        @click="activeTab = 'descricao'"
                        :class="activeTab === 'descricao' ? 'text-[#003366] border-[#003366]' : 'text-slate-500 border-transparent'"
                        class="px-6 py-4 font-bold text-sm border-b-2 transition-colors hover:text-[#003366]"
                    >
                        <i class="fa-solid fa-info-circle mr-2"></i> Descrição
                    </button>
                    <button 
                        @click="activeTab = 'materiais'"
                        :class="activeTab === 'materiais' ? 'text-[#003366] border-[#003366]' : 'text-slate-500 border-transparent'"
                        class="px-6 py-4 font-bold text-sm border-b-2 transition-colors hover:text-[#003366]"
                    >
                        <i class="fa-solid fa-folder-open mr-2"></i> Materiais
                    </button>
                    <button 
                        @click="activeTab = 'comentarios'"
                        :class="activeTab === 'comentarios' ? 'text-[#003366] border-[#003366]' : 'text-slate-500 border-transparent'"
                        class="px-6 py-4 font-bold text-sm border-b-2 transition-colors hover:text-[#003366]"
                    >
                        <i class="fa-solid fa-comments mr-2"></i> Comentários <span class="ml-1 px-2 py-0.5 bg-slate-200 text-slate-600 rounded-full text-xs">12</span>
                    </button>
                </div>
                
                {{-- Tab Content --}}
                <div class="p-6">
                    {{-- Descrição --}}
                    <div x-show="activeTab === 'descricao'" class="space-y-4">
                        <p class="text-slate-700 leading-relaxed">
                            {{ $aula->descricao ?? 'Nesta aula, vamos explorar os fundamentos teológicos essenciais para compreender a doutrina cristã. Abordaremos conceitos importantes sobre a natureza de Deus, Sua revelação e a aplicação prática desses ensinamentos.' }}
                        </p>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <h4 class="font-black text-sm text-slate-800 mb-2">Objetivos de Aprendizagem:</h4>
                            <ul class="space-y-2 text-sm text-slate-700">
                                <li class="flex items-start">
                                    <i class="fa-solid fa-check-circle text-blue-600 mt-0.5 mr-2"></i>
                                    <span>Compreender os atributos fundamentais de Deus</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fa-solid fa-check-circle text-blue-600 mt-0.5 mr-2"></i>
                                    <span>Identificar as formas de revelação divina</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fa-solid fa-check-circle text-blue-600 mt-0.5 mr-2"></i>
                                    <span>Aplicar princípios teológicos no contexto contemporâneo</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    {{-- Materiais --}}
                    <div x-show="activeTab === 'materiais'" class="space-y-3">
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Apostila - Teologia Sistemática</p>
                                    <p class="text-xs text-slate-500">PDF • 2.5 MB</p>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-[#003366] text-white rounded-lg text-xs font-bold hover:bg-[#002244]">
                                <i class="fa-solid fa-download mr-1"></i> Baixar
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-file-powerpoint"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Slides da Aula</p>
                                    <p class="text-xs text-slate-500">PPTX • 4.2 MB</p>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-[#003366] text-white rounded-lg text-xs font-bold hover:bg-[#002244]">
                                <i class="fa-solid fa-download mr-1"></i> Baixar
                            </button>
                        </div>
                    </div>
                    
                    {{-- Comentários --}}
                    <div x-show="activeTab === 'comentarios'" class="space-y-4">
                        {{-- Input de Comentário --}}
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-[#003366] text-white rounded-lg flex items-center justify-center text-sm font-black flex-shrink-0">
                                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <textarea 
                                    class="w-full p-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm resize-none"
                                    rows="3"
                                    placeholder="Adicione um comentário..."
                                ></textarea>
                                <div class="flex justify-end mt-2">
                                    <button class="px-4 py-2 bg-[#003366] text-white rounded-lg text-xs font-bold hover:bg-[#002244]">
                                        Comentar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Lista de Comentários --}}
                        <div class="space-y-4 pt-4 border-t border-slate-200">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-blue-500 text-white rounded-lg flex items-center justify-center text-sm font-black flex-shrink-0">
                                    M
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <p class="text-sm font-bold text-slate-800">Maria Silva</p>
                                        <span class="text-xs text-slate-400">• há 2 horas</span>
                                    </div>
                                    <p class="text-sm text-slate-700">Excelente aula! Os conceitos foram muito bem explicados. Agradeço pela clareza na exposição.</p>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <button class="text-xs font-bold text-slate-500 hover:text-blue-600">
                                            <i class="fa-regular fa-thumbs-up mr-1"></i> Curtir (5)
                                        </button>
                                        <button class="text-xs font-bold text-slate-500 hover:text-blue-600">
                                            <i class="fa-solid fa-reply mr-1"></i> Responder
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Sidebar: Lista de Aulas do Curso --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-8">
                <div class="p-4 bg-[#003366] text-white">
                    <h3 class="font-black text-sm uppercase tracking-widest">Conteúdo do Curso</h3>
                    <p class="text-xs text-blue-200 mt-1">{{ $totalAulas ?? 20 }} aulas • {{ $totalModulos ?? 5 }} módulos</p>
                </div>
                
                <div class="max-h-[600px] overflow-y-auto">
                    @foreach($todasAulas ?? [] as $moduloId => $aulasModulo)
                        <div class="border-b border-slate-100">
                            <div class="p-4 bg-slate-50">
                                <p class="text-xs font-black text-slate-600 uppercase tracking-widest">Módulo {{ $loop->iteration }}</p>
                                <p class="text-sm font-bold text-slate-800 mt-1">{{ $aulasModulo[0]->modulo_titulo ?? 'Módulo' }}</p>
                            </div>
                            
                            <div class="divide-y divide-slate-100">
                                @foreach($aulasModulo as $aulaItem)
                                    <a 
                                        href="{{ route('aluno.aula', $aulaItem->id) }}"
                                        class="flex items-center space-x-3 p-3 hover:bg-blue-50 transition-colors group
                                            {{ ($aula->id ?? 0) == $aulaItem->id ? 'bg-blue-50 border-l-4 border-blue-600' : '' }}"
                                    >
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                                            {{ isset($progresso[$aulaItem->id]) && $progresso[$aulaItem->id] 
                                                ? 'bg-green-500 text-white' 
                                                : 'bg-slate-100 text-slate-400 group-hover:bg-blue-100 group-hover:text-blue-600' }}">
                                            <i class="fa-solid {{ isset($progresso[$aulaItem->id]) && $progresso[$aulaItem->id] ? 'fa-check' : 'fa-play' }} text-xs"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-800 truncate">{{ $aulaItem->titulo }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $aulaItem->duracao ?? '25' }} min</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    
                    {{-- Mock se não houver dados --}}
                    @if(!isset($todasAulas) || count($todasAulas) == 0)
                        <div class="p-6 text-center">
                            <i class="fa-solid fa-book-open text-slate-300 text-3xl mb-2"></i>
                            <p class="text-sm text-slate-500">Nenhuma aula disponível</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-desktop-layout>
