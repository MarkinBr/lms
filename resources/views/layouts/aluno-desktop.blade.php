@php
    $aluno = auth()->user();
    $fotoAluno = null;

    if (!empty($aluno?->foto)) {
        $fotoAluno = str_starts_with($aluno->foto, 'http')
            ? $aluno->foto
            : asset('storage/' . ltrim($aluno->foto, '/'));
    }
@endphp
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CETS - Portal do Aluno')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/premium-classroom.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .animate-slide-in { animation: slideInRight 0.4s ease-out; }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full overflow-hidden">
    <div class="flex h-screen bg-slate-50" x-data="{ sidebarOpen: true }">
        <aside
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            class="w-72 bg-[#003366] text-white flex-shrink-0 flex flex-col shadow-2xl relative overflow-hidden"
        >
            <div class="p-8 border-b border-white/10 relative z-10">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 bg-white rounded-2xl border-2 border-white/20 flex items-center justify-center overflow-hidden shadow-lg">
                        <img src="{{ asset('assets/images/cetsadp.png') }}" alt="CETSADP" class="w-full h-full object-contain p-1">
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tight">CETSADP</h1>
                        <p class="text-xs text-blue-200 font-bold uppercase tracking-widest">Portal do Aluno</p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-white/10">
                <div class="flex items-center space-x-4">
                    @if($fotoAluno)
                        <img src="{{ $fotoAluno }}" alt="Foto do aluno" class="w-12 h-12 rounded-xl object-cover border border-white/20 bg-white/10">
                    @else
                        <div class="w-12 h-12 bg-white/10 rounded-xl border border-white/20 flex items-center justify-center text-lg font-black backdrop-blur-sm">
                            {{ strtoupper(substr($aluno->name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black truncate">{{ $aluno->name ?? 'Aluno' }}</p>
                        <div class="flex items-center space-x-1 mt-1">
                            <i class="fa-solid fa-graduation-cap text-yellow-400 text-xs"></i>
                            <p class="text-[10px] font-bold text-blue-200 uppercase tracking-widest">Aluno</p>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                <a href="{{ route('aluno.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.dashboard') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                    <i class="fa-solid fa-house {{ request()->routeIs('aluno.dashboard') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                    <span class="text-sm font-bold flex-1">Dashboard</span>
                </a>
                <a href="{{ route('aluno.cursos') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.cursos*') || request()->routeIs('aluno.curso.show') || request()->routeIs('aluno.aula*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                    <i class="fa-solid fa-book-open {{ request()->routeIs('aluno.cursos*') || request()->routeIs('aluno.curso.show') || request()->routeIs('aluno.aula*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                    <span class="text-sm font-bold flex-1">Meus Cursos</span>
                </a>
                <a href="{{ route('aluno.calendario') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.calendario*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                    <i class="fa-solid fa-calendar-days {{ request()->routeIs('aluno.calendario*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                    <span class="text-sm font-bold flex-1">Calendário</span>
                </a>
                <a href="{{ route('aluno.notas') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.notas*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                    <i class="fa-solid fa-star {{ request()->routeIs('aluno.notas*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                    <span class="text-sm font-bold flex-1">Minhas Notas</span>
                </a>
                <a href="{{ route('aluno.frequencia') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.frequencia*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                    <i class="fa-solid fa-clipboard-check {{ request()->routeIs('aluno.frequencia*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                    <span class="text-sm font-bold flex-1">Frequência</span>
                </a>
                <a href="{{ route('aluno.financeiro') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.financeiro*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                    <i class="fa-solid fa-wallet {{ request()->routeIs('aluno.financeiro*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                    <span class="text-sm font-bold flex-1">Financeiro</span>
                </a>

                <div class="pt-4 mt-4 border-t border-white/10">
                    <p class="text-[10px] font-black uppercase text-white/40 tracking-widest mb-2 px-4">Comunicação</p>
                    <a href="{{ route('aluno.comunicacao') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.comunicacao*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                        <i class="fa-solid fa-comments {{ request()->routeIs('aluno.comunicacao*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                        <span class="text-sm font-bold flex-1">Chat da Turma</span>
                    </a>
                    <a href="{{ route('aluno.forum') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.forum*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                        <i class="fa-solid fa-users-rectangle {{ request()->routeIs('aluno.forum*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                        <span class="text-sm font-bold flex-1">Fórum</span>
                    </a>
                    <a href="{{ route('aluno.secretaria') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.secretaria*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                        <i class="fa-solid fa-headset {{ request()->routeIs('aluno.secretaria*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                        <span class="text-sm font-bold flex-1">Secretaria</span>
                    </a>
                </div>

                <div class="pt-4 mt-4 border-t border-white/10">
                    <p class="text-[10px] font-black uppercase text-white/40 tracking-widest mb-2 px-4">Documentos</p>
                    <a href="{{ route('aluno.certificados') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.certificados*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                        <i class="fa-solid fa-certificate {{ request()->routeIs('aluno.certificados*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                        <span class="text-sm font-bold flex-1">Certificados</span>
                    </a>
                    <a href="{{ route('aluno.documentos-inscricao') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all group {{ request()->routeIs('aluno.documentos*') ? 'bg-white/10 text-white shadow-lg shadow-black/10' : 'text-blue-100 hover:bg-white/5 hover:text-white' }}">
                        <i class="fa-solid fa-folder-open {{ request()->routeIs('aluno.documentos*') ? 'text-yellow-400' : 'text-blue-300 group-hover:text-white' }} transition-colors"></i>
                        <span class="text-sm font-bold flex-1">Documentos da inscrição</span>
                    </a>
                </div>
            </nav>

            <div class="p-4 border-t border-white/10">
                <form method="POST" action="{{ route('aluno.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-colors group">
                        <i class="fa-solid fa-right-from-bracket text-red-400 group-hover:text-red-300"></i>
                        <span class="text-sm font-bold">Sair</span>
                    </button>
                </form>
                <div class="mt-4 text-center opacity-40">
                    <p class="text-[9px] font-black uppercase tracking-widest">Versão 2.5.0</p>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between shadow-sm z-20">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-bars text-slate-600"></i>
                    </button>
                    <div>
                        <h2 class="text-lg font-black text-slate-800">@yield('header_title', 'Dashboard')</h2>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">@yield('header_subtitle', 'Bem-vindo ao portal')</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="relative hidden lg:block">
                        <input type="text" placeholder="Buscar..." class="w-64 pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    </div>

                    <a href="{{ route('aluno.perfil') }}" class="flex items-center space-x-3 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                        @if($fotoAluno)
                            <img src="{{ $fotoAluno }}" alt="Foto do aluno" class="w-9 h-9 rounded-lg object-cover border border-slate-200">
                        @else
                            <div class="w-9 h-9 bg-[#003366] text-white rounded-lg flex items-center justify-center text-sm font-black">
                                {{ strtoupper(substr($aluno->name ?? 'A', 0, 1)) }}
                            </div>
                        @endif
                        <div class="hidden xl:block text-left">
                            <p class="text-sm font-black text-slate-800 leading-tight">{{ $aluno->name ?? 'Aluno' }}</p>
                            <p class="text-[11px] text-slate-400">{{ $aluno->email ?? '' }}</p>
                        </div>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="p-8 max-w-[1400px] mx-auto animate-fade-in">
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm">
                            <p class="font-bold">Sucesso</p>
                            <p class="text-sm">{{ session('success') }}</p>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm">
                            <p class="font-bold">Erro</p>
                            <p class="text-sm">{{ session('error') }}</p>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    <script type="module" src="{{ asset('js/premium-classroom.js') }}"></script>
    @stack('scripts')
</body>
</html>
