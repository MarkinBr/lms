{{-- Layout Principal Desktop CETS --}}
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'CETS - Portal do Aluno' }}</title>
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Animações Customizadas */
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
        
        .animate-slide-in {
            animation: slideInRight 0.4s ease-out;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Scrollbar Customizada */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="h-full overflow-hidden">
    <div class="flex h-screen bg-slate-50" x-data="{ sidebarOpen: true, notificationsOpen: false, profileOpen: false }">
        
        {{-- SIDEBAR LATERAL --}}
        <aside 
            x-show="sidebarOpen" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            class="w-72 bg-[#003366] text-white flex-shrink-0 flex flex-col shadow-2xl relative overflow-hidden"
        >
            {{-- Logo e Header --}}
            <div class="p-8 border-b border-white/10 relative z-10">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 bg-white/10 rounded-2xl border-2 border-white/20 flex items-center justify-center backdrop-blur-sm">
                        <i class="fa-solid fa-book-bible text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tight">CETS</h1>
                        <p class="text-xs text-blue-200 font-bold uppercase tracking-widest">Portal do Aluno</p>
                    </div>
                </div>
            </div>
            
            {{-- Perfil do Aluno --}}
            <div class="p-6 border-b border-white/10">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-white/10 rounded-xl border border-white/20 flex items-center justify-center text-lg font-black backdrop-blur-sm">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black truncate">{{ auth()->user()->name ?? 'Aluno' }}</p>
                        <div class="flex items-center space-x-1 mt-1">
                            <i class="fa-solid fa-graduation-cap text-yellow-400 text-xs"></i>
                            <p class="text-[10px] font-bold text-blue-200 uppercase tracking-widest">Bacharel em Teologia</p>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Menu de Navegação --}}
            <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                <x-desktop-nav-item 
                    href="{{ route('aluno.dashboard') }}" 
                    icon="fa-house" 
                    label="Dashboard"
                    :active="request()->routeIs('aluno.dashboard')"
                />
                
                <x-desktop-nav-item 
                    href="{{ route('aluno.cursos') }}" 
                    icon="fa-book-open" 
                    label="Meus Cursos"
                    :active="request()->routeIs('aluno.cursos*')"
                />
                
                <x-desktop-nav-item 
                    href="{{ route('aluno.calendario') }}" 
                    icon="fa-calendar-days" 
                    label="Calendário"
                    :active="request()->routeIs('aluno.calendario')"
                />
                
                <x-desktop-nav-item 
                    href="{{ route('aluno.notas') }}" 
                    icon="fa-star" 
                    label="Minhas Notas"
                    :active="request()->routeIs('aluno.notas')"
                />
                
                <x-desktop-nav-item 
                    href="{{ route('aluno.frequencia') }}" 
                    icon="fa-clipboard-check" 
                    label="Frequência"
                    :active="request()->routeIs('aluno.frequencia')"
                />
                
                <x-desktop-nav-item 
                    href="{{ route('aluno.financeiro') }}" 
                    icon="fa-wallet" 
                    label="Financeiro"
                    :active="request()->routeIs('aluno.financeiro*')"
                />
                
                <div class="pt-4 mt-4 border-t border-white/10">
                    <p class="text-[10px] font-black uppercase text-white/40 tracking-widest mb-2 px-4">Comunicação</p>
                    
                    <x-desktop-nav-item 
                        href="{{ route('aluno.comunicacao') }}" 
                        icon="fa-comments" 
                        label="Chat da Turma"
                    />
                    
                    <x-desktop-nav-item 
                        href="{{ route('aluno.forum') }}" 
                        icon="fa-users-rectangle" 
                        label="Fórum"
                    />
                    
                    <x-desktop-nav-item 
                        href="{{ route('aluno.secretaria') }}" 
                        icon="fa-headset" 
                        label="Secretaria"
                    />
                </div>
                
                <div class="pt-4 mt-4 border-t border-white/10">
                    <p class="text-[10px] font-black uppercase text-white/40 tracking-widest mb-2 px-4">Documentos</p>
                    
                    <x-desktop-nav-item 
                        href="{{ route('aluno.certificados') }}" 
                        icon="fa-certificate" 
                        label="Certificados"
                    />
                    
                    <x-desktop-nav-item 
                        href="{{ route('aluno.documentos') }}" 
                        icon="fa-folder-open" 
                        label="Documentos"
                    />
                </div>
            </nav>
            
            {{-- Footer Sidebar --}}
            <div class="p-4 border-t border-white/10">
                <form method="POST" action="{{ route('aluno.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-colors group">
                        <i class="fa-solid fa-right-from-bracket text-red-400 group-hover:text-red-300"></i>
                        <span class="text-sm font-bold">Sair</span>
                    </button>
                </form>
                
                <div class="mt-4 text-center opacity-40">
                    <p class="text-[9px] font-black uppercase tracking-widest">Versão 2.4.0</p>
                </div>
            </div>
            
            {{-- Background Decoration --}}
            <i class="fa-solid fa-cross absolute -right-10 -bottom-10 text-white/5 text-[15rem] rotate-12 pointer-events-none"></i>
        </aside>
        
        {{-- CONTEÚDO PRINCIPAL --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between shadow-sm z-20">
                <div class="flex items-center space-x-4">
                    <button 
                        @click="sidebarOpen = !sidebarOpen"
                        class="w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition-colors"
                    >
                        <i class="fa-solid fa-bars text-slate-600"></i>
                    </button>
                    
                    <div>
                        <h2 class="text-lg font-black text-slate-800">{{ $pageTitle ?? 'Dashboard' }}</h2>
                        @isset($pageSubtitle)
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">{{ $pageSubtitle }}</p>
                        @endisset
                    </div>
                </div>
                
                <div class="flex items-center space-x-3">
                    {{-- Busca Rápida --}}
                    <div class="relative hidden lg:block">
                        <input 
                            type="text" 
                            placeholder="Buscar..." 
                            class="w-64 pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm"
                        >
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    </div>
                    
                    {{-- Notificações --}}
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="relative w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition-colors"
                        >
                            <i class="fa-solid fa-bell text-slate-600"></i>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        
                        {{-- Dropdown Notificações --}}
                        <div 
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-50"
                        >
                            <div class="p-4 border-b border-slate-100">
                                <h3 class="font-black text-sm text-slate-800">Notificações</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                {{-- Exemplo de Notificação --}}
                                <div class="p-4 hover:bg-slate-50 border-b border-slate-100 cursor-pointer">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fa-solid fa-book text-xs"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-slate-800">Nova aula disponível</p>
                                            <p class="text-xs text-slate-500 mt-1">Teologia Sistemática - Aula 05</p>
                                            <p class="text-[10px] text-slate-400 mt-1">Há 2 horas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-slate-50 text-center">
                                <a href="#" class="text-xs font-bold text-blue-600 hover:text-blue-700">Ver todas</a>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Menu Perfil --}}
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="flex items-center space-x-3 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors"
                        >
                            <div class="w-8 h-8 bg-[#003366] text-white rounded-lg flex items-center justify-center text-sm font-black">
                                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        
                        {{-- Dropdown Perfil --}}
                        <div 
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden z-50"
                        >
                            <div class="p-4 border-b border-slate-100">
                                <p class="font-black text-sm text-slate-800">{{ auth()->user()->name ?? 'Aluno' }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ auth()->user()->email ?? '' }}</p>
                            </div>
                            <div class="p-2">
                                <a href="{{ route('aluno.perfil') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50">
                                    <i class="fa-solid fa-user text-slate-400"></i>
                                    <span class="text-sm font-bold text-slate-700">Meu Perfil</span>
                                </a>
                                <a href="{{ route('aluno.configuracoes') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-50">
                                    <i class="fa-solid fa-gear text-slate-400"></i>
                                    <span class="text-sm font-bold text-slate-700">Configurações</span>
                                </a>
                            </div>
                            <div class="p-2 border-t border-slate-100">
                                <form method="POST" action="{{ route('aluno.logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-red-50 text-red-600">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                        <span class="text-sm font-bold">Sair</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            {{-- Área de Conteúdo com Scroll --}}
            <main class="flex-1 overflow-y-auto">
                <div class="p-8 max-w-[1400px] mx-auto animate-fade-in">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
