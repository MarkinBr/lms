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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CETS - Portal do Aluno')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <div x-data="{ menuOpen: false }" class="min-h-screen flex flex-col">
        <header class="sticky top-0 z-40 bg-[#003366] text-white shadow-lg">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="menuOpen = true" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <img src="{{ asset('assets/images/cetsadp.png') }}" alt="CETSADP" class="w-10 h-10 rounded-xl bg-white object-contain p-1 shadow">
                    <div class="min-w-0">
                        <p class="text-sm font-black truncate">@yield('header_title', 'Portal do Aluno')</p>
                        <p class="text-[11px] text-blue-200 truncate">{{ $aluno->name ?? 'Aluno' }}</p>
                    </div>
                </div>
                <a href="{{ route('aluno.perfil') }}" class="shrink-0">
                    @if($fotoAluno)
                        <img src="{{ $fotoAluno }}" alt="Foto do aluno" class="w-10 h-10 rounded-xl object-cover border border-white/20">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center font-black">
                            {{ strtoupper(substr($aluno->name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                </a>
            </div>
        </header>

        <div x-show="menuOpen" x-cloak class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-950/50" @click="menuOpen = false"></div>
            <aside x-transition class="absolute left-0 top-0 h-full w-80 max-w-[88vw] bg-[#003366] text-white shadow-2xl overflow-y-auto">
                <div class="p-5 border-b border-white/10 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ asset('assets/images/cetsadp.png') }}" alt="CETSADP" class="w-11 h-11 rounded-xl bg-white object-contain p-1">
                        <div class="min-w-0">
                            <p class="font-black truncate">CETSADP</p>
                            <p class="text-xs text-blue-200">Portal do Aluno</p>
                        </div>
                    </div>
                    <button @click="menuOpen = false" class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="p-5 border-b border-white/10 flex items-center gap-3">
                    @if($fotoAluno)
                        <img src="{{ $fotoAluno }}" alt="Foto do aluno" class="w-12 h-12 rounded-xl object-cover border border-white/20">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center font-black text-lg">
                            {{ strtoupper(substr($aluno->name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="font-black truncate">{{ $aluno->name ?? 'Aluno' }}</p>
                        <p class="text-xs text-blue-200 truncate">{{ $aluno->email ?? '' }}</p>
                    </div>
                </div>

                <nav class="p-4 space-y-2 text-sm font-bold">
                    @php
                        $navItems = [
                            ['label' => 'Dashboard', 'route' => 'aluno.dashboard', 'icon' => 'fa-house', 'active' => request()->routeIs('aluno.dashboard')],
                            ['label' => 'Meus Cursos', 'route' => 'aluno.cursos', 'icon' => 'fa-book-open', 'active' => request()->routeIs('aluno.cursos*') || request()->routeIs('aluno.curso.show') || request()->routeIs('aluno.aula*')],
                            ['label' => 'Calendário', 'route' => 'aluno.calendario', 'icon' => 'fa-calendar-days', 'active' => request()->routeIs('aluno.calendario*')],
                            ['label' => 'Financeiro', 'route' => 'aluno.financeiro', 'icon' => 'fa-wallet', 'active' => request()->routeIs('aluno.financeiro*')],
                            ['label' => 'Secretaria', 'route' => 'aluno.secretaria', 'icon' => 'fa-headset', 'active' => request()->routeIs('aluno.secretaria*')],
                            ['label' => 'Perfil', 'route' => 'aluno.perfil', 'icon' => 'fa-user', 'active' => request()->routeIs('aluno.perfil*')],
                        ];
                    @endphp
                    @foreach($navItems as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl {{ $item['active'] ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' }}">
                            <i class="fa-solid {{ $item['icon'] }} {{ $item['active'] ? 'text-yellow-400' : 'text-blue-300' }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="p-4 border-t border-white/10 mt-4">
                    <form method="POST" action="{{ route('aluno.logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-2xl bg-white/10 hover:bg-white/15 font-bold">
                            <i class="fa-solid fa-right-from-bracket text-red-300"></i>
                            <span>Sair</span>
                        </button>
                    </form>
                </div>
            </aside>
        </div>

        <main class="flex-1 px-4 py-5 pb-24">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-xl shadow-sm">
                    <p class="font-bold">Sucesso</p>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-xl shadow-sm">
                    <p class="font-bold">Erro</p>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            @endif
            @yield('content')
        </main>

        <nav class="fixed bottom-0 inset-x-0 z-40 bg-white border-t border-slate-200 shadow-[0_-10px_25px_rgba(15,23,42,0.08)]">
            <div class="grid grid-cols-5">
                <a href="{{ route('aluno.dashboard') }}" class="flex flex-col items-center justify-center py-3 text-[11px] font-bold {{ request()->routeIs('aluno.dashboard') ? 'text-[#003366]' : 'text-slate-400' }}">
                    <i class="fa-solid fa-house text-base mb-1"></i><span>Início</span>
                </a>
                <a href="{{ route('aluno.cursos') }}" class="flex flex-col items-center justify-center py-3 text-[11px] font-bold {{ request()->routeIs('aluno.cursos*') || request()->routeIs('aluno.curso.show') || request()->routeIs('aluno.aula*') ? 'text-[#003366]' : 'text-slate-400' }}">
                    <i class="fa-solid fa-book-open text-base mb-1"></i><span>Cursos</span>
                </a>
                <a href="{{ route('aluno.calendario') }}" class="flex flex-col items-center justify-center py-3 text-[11px] font-bold {{ request()->routeIs('aluno.calendario*') ? 'text-[#003366]' : 'text-slate-400' }}">
                    <i class="fa-solid fa-calendar-days text-base mb-1"></i><span>Agenda</span>
                </a>
                <a href="{{ route('aluno.financeiro') }}" class="flex flex-col items-center justify-center py-3 text-[11px] font-bold {{ request()->routeIs('aluno.financeiro*') ? 'text-[#003366]' : 'text-slate-400' }}">
                    <i class="fa-solid fa-wallet text-base mb-1"></i><span>Financeiro</span>
                </a>
                <a href="{{ route('aluno.perfil') }}" class="flex flex-col items-center justify-center py-3 text-[11px] font-bold {{ request()->routeIs('aluno.perfil*') ? 'text-[#003366]' : 'text-slate-400' }}">
                    <i class="fa-solid fa-user text-base mb-1"></i><span>Perfil</span>
                </a>
            </div>
        </nav>
    </div>
    @stack('scripts')
</body>
</html>
