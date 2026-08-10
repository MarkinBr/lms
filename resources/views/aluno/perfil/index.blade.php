@extends('layouts.aluno')

@section('title', 'Meu Perfil')
@section('header_title', 'Meu Perfil')
@section('header_subtitle', 'Atualize seus dados e acompanhe sua jornada')

@section('content')
    {{-- Background Gradiente Moderno --}}
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-slate-50">
        
        {{-- ============================= --}}
        {{-- HERO HEADER PROFISSIONAL --}}
        {{-- ============================= --}}
        <div class="relative bg-gradient-to-r from-[#003366] via-blue-900 to-indigo-900 text-white overflow-hidden">
            {{-- Padrão geométrico de fundo --}}
            <div class="absolute inset-0 opacity-5">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <circle cx="20" cy="20" r="1" fill="currentColor"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)"/>
                </svg>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-6 py-8">
                {{-- Breadcrumb Elegante --}}
                <nav class="flex items-center gap-2 text-sm text-blue-200 mb-8">
                    <a href="{{ route('aluno.dashboard') }}" class="hover:text-white transition-colors duration-200 flex items-center gap-1.5">
                        <i class="fa-solid fa-house text-xs"></i>
                        <span>Painel</span>
                    </a>
                    <i class="fa-solid fa-chevron-right text-xs opacity-50"></i>
                    <span class="text-white font-medium">Perfil Acadêmico</span>
                </nav>
                
                {{-- Header com Avatar e Info --}}
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
                    <div class="flex items-center gap-6">
                        {{-- Avatar com efeito hover --}}
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-blue-400 to-indigo-400 rounded-3xl blur opacity-25 group-hover:opacity-75 transition duration-300"></div>
                            @if($aluno->foto)
                                <img src="{{ asset('storage/' . $aluno->foto) }}" 
                                     alt="{{ $aluno->name }}" 
                                     class="relative w-28 h-28 rounded-2xl object-cover border-4 border-white/20 shadow-2xl ring-2 ring-white/50">
                            @else
                                <div class="relative w-28 h-28 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 flex items-center justify-center text-4xl font-bold border-4 border-white/20 shadow-2xl ring-2 ring-white/50">
                                    {{ substr($aluno->name, 0, 1) }}
                                </div>
                            @endif
                            
                            @if($aluno->email_verified_at)
                                <div class="absolute -bottom-2 -right-2 bg-emerald-500 text-white rounded-full w-9 h-9 flex items-center justify-center border-3 border-white shadow-lg">
                                    <i class="fa-solid fa-badge-check"></i>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Informações do Usuário --}}
                        <div>
                            <h1 class="text-4xl font-bold mb-2 bg-clip-text text-transparent bg-gradient-to-r from-white to-blue-100">
                                {{ $aluno->name }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-4 text-blue-100 mb-3">
                                <span class="flex items-center gap-2 text-sm">
                                    <i class="fa-solid fa-envelope"></i>
                                    {{ $aluno->email }}
                                </span>
                                @if($aluno->profissao)
                                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-sm rounded-full text-sm border border-white/20">
                                        <i class="fa-solid fa-briefcase"></i>
                                        {{ $aluno->profissao }}
                                    </span>
                                @endif
                            </div>
                            @if($aluno->bio)
                                <p class="text-blue-100/90 text-sm max-w-2xl italic">
                                    "{{ Str::limit($aluno->bio, 100) }}"
                                </p>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Ações Rápidas --}}
                    <div class="flex gap-3">
                        <a href="{{ route('aluno.perfil.exportar-dados') }}" 
                           class="group px-5 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/30 rounded-xl transition-all duration-300 flex items-center gap-2.5 text-white font-medium shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                            <i class="fa-solid fa-cloud-arrow-down group-hover:scale-110 transition-transform"></i>
                            <span>Exportar Dados</span>
                        </a>
                    </div>
                </div>
                
                {{-- Cards de Estatísticas Rápidas --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 hover:bg-white/15 transition-all duration-300">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-blue-200 text-sm font-medium">Cursos Ativos</span>
                            <i class="fa-solid fa-book-open text-blue-300"></i>
                        </div>
                        <p class="text-3xl font-bold text-white">{{ $estatisticas['cursos_em_andamento'] }}</p>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 hover:bg-white/15 transition-all duration-300">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-emerald-200 text-sm font-medium">Concluídos</span>
                            <i class="fa-solid fa-graduation-cap text-emerald-300"></i>
                        </div>
                        <p class="text-3xl font-bold text-white">{{ $estatisticas['cursos_concluidos'] }}</p>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 hover:bg-white/15 transition-all duration-300">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-amber-200 text-sm font-medium">Certificados</span>
                            <i class="fa-solid fa-award text-amber-300"></i>
                        </div>
                        <p class="text-3xl font-bold text-white">{{ $estatisticas['certificados'] }}</p>
                    </div>
                    
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 hover:bg-white/15 transition-all duration-300">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-purple-200 text-sm font-medium">Média Geral</span>
                            <i class="fa-solid fa-chart-line text-purple-300"></i>
                        </div>
                        <p class="text-3xl font-bold text-white">{{ $estatisticas['media_notas'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================= --}}
        {{-- MENSAGENS DE FEEDBACK --}}
        {{-- ============================= --}}
        <div class="max-w-7xl mx-auto px-6 -mt-4 relative z-10">
            @if(session('success'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-transition
                     class="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white p-5 rounded-xl shadow-lg flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <i class="fa-solid fa-circle-check text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Sucesso!</h4>
                            <p class="text-emerald-50">{{ session('success') }}</p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-white/80 hover:text-white text-2xl font-bold px-2">
                        &times;
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-transition
                     class="bg-gradient-to-r from-red-500 to-red-600 text-white p-5 rounded-xl shadow-lg flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <i class="fa-solid fa-circle-exclamation text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">Erro!</h4>
                            <p class="text-red-50">{{ session('error') }}</p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-white/80 hover:text-white text-2xl font-bold px-2">
                        &times;
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-transition
                     class="bg-white border-l-4 border-red-500 p-5 rounded-xl shadow-lg mb-4">
                    <div class="flex items-start gap-4">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 text-2xl mt-1"></i>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800 mb-3">Corrija os seguintes erros:</h4>
                            <ul class="space-y-2">
                                @foreach($errors->all() as $error)
                                    <li class="flex items-start gap-2 text-gray-700">
                                        <i class="fa-solid fa-circle text-red-400 text-xs mt-1.5"></i>
                                        <span>{{ $error }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <button @click="show = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold px-2">
                            &times;
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- ============================= --}}
        {{-- CONTEÚDO PRINCIPAL --}}
        {{-- ============================= --}}
        <div class="max-w-7xl mx-auto px-6 pb-12 mt-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                {{-- ============================= --}}
                {{-- SIDEBAR ESQUERDA (3 colunas) --}}
                {{-- ============================= --}}
                <div class="lg:col-span-3 space-y-6">
                    
                    {{-- Card de Progresso Geral --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-300">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-chart-pie text-[#003366]"></i>
                            </div>
                            Progresso Geral
                        </h3>
                        
                        @if($progressoGeral['total_aulas'] > 0)
                            {{-- Gráfico Circular de Progresso --}}
                            <div class="relative w-40 h-40 mx-auto mb-4">
                                <svg class="transform -rotate-90" viewBox="0 0 160 160">
                                    {{-- Círculo de fundo --}}
                                    <circle cx="80" cy="80" r="70" fill="none" stroke="#E5E7EB" stroke-width="12"/>
                                    {{-- Círculo de progresso --}}
                                    <circle cx="80" cy="80" r="70" fill="none" stroke="url(#gradient)" stroke-width="12"
                                            stroke-dasharray="{{ 2 * 3.14159 * 70 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 70 * (1 - $progressoGeral['percentual'] / 100) }}"
                                            stroke-linecap="round"
                                            class="transition-all duration-1000"/>
                                    <defs>
                                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#003366"/>
                                            <stop offset="100%" style="stop-color:#3B82F6"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-4xl font-bold text-[#003366]">{{ $progressoGeral['percentual'] }}%</span>
                                    <span class="text-xs text-gray-500 mt-1">Completo</span>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 flex items-center gap-2">
                                        <i class="fa-solid fa-play-circle text-blue-500"></i>
                                        Aulas Assistidas
                                    </span>
                                    <span class="font-bold text-gray-800">{{ $progressoGeral['aulas_assistidas'] }}/{{ $progressoGeral['total_aulas'] }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 flex items-center gap-2">
                                        <i class="fa-solid fa-clock text-amber-500"></i>
                                        Tempo de Estudo
                                    </span>
                                    <span class="font-bold text-gray-800">{{ number_format($estatisticas['total_horas_estudo'] / 60, 1) }}h</span>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <i class="fa-solid fa-inbox text-5xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 text-sm">Nenhum curso iniciado ainda</p>
                            </div>
                        @endif
                    </div>

                    {{-- Card de Estatísticas --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-300">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-chart-simple text-purple-600"></i>
                            </div>
                            Estatísticas
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors">
                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-book text-[#003366]"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-600 font-medium uppercase tracking-wide">Total de Cursos</p>
                                    <p class="text-2xl font-bold text-gray-800">{{ $estatisticas['total_cursos'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition-colors">
                                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-star text-emerald-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-600 font-medium uppercase tracking-wide">Média de Notas</p>
                                    <p class="text-2xl font-bold text-gray-800">{{ $estatisticas['media_notas'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors">
                                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-play text-purple-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-600 font-medium uppercase tracking-wide">Em Andamento</p>
                                    <p class="text-2xl font-bold text-gray-800">{{ $estatisticas['cursos_em_andamento'] }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-xl hover:bg-amber-100 transition-colors">
                                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-calendar-days text-amber-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-600 font-medium uppercase tracking-wide">Aluno há</p>
                                    <p class="text-2xl font-bold text-gray-800">{{ $estatisticas['dias_desde_cadastro'] }} <span class="text-sm text-gray-500">dias</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card de Segurança --}}
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl shadow-sm border border-amber-200 p-6 hover:shadow-md transition-shadow duration-300">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-shield-halved text-amber-600"></i>
                            </div>
                            Segurança
                        </h3>
                        
                        <form action="{{ route('aluno.perfil.alterar-senha') }}" method="POST" class="space-y-4">
                            @csrf
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Senha Atual</label>
                                <div class="relative">
                                    <input type="password" 
                                           name="senha_atual" 
                                           required 
                                           class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition pl-10">
                                    <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nova Senha</label>
                                <div class="relative">
                                    <input type="password" 
                                           name="nova_senha" 
                                           required 
                                           minlength="8"
                                           class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition pl-10">
                                    <i class="fa-solid fa-key absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                                <p class="text-xs text-gray-600 mt-2">Mínimo 8 caracteres</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Nova Senha</label>
                                <div class="relative">
                                    <input type="password" 
                                           name="nova_senha_confirmation" 
                                           required 
                                           class="w-full rounded-xl border-gray-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 transition pl-10">
                                    <i class="fa-solid fa-check absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-bold py-3 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-shield-check"></i> 
                                Atualizar Senha
                            </button>
                        </form>

                        @if($aluno->senha_alterada_em)
                            <p class="text-xs text-gray-600 mt-4 text-center flex items-center justify-center gap-1">
                                <i class="fa-solid fa-clock"></i>
                                Última alteração {{ $aluno->senha_alterada_em->diffForHumans() }}
                            </p>
                        @endif
                    </div>

                </div>

                {{-- ============================= --}}
                {{-- CONTEÚDO PRINCIPAL (9 colunas) --}}
                {{-- ============================= --}}
                <div class="lg:col-span-9">
                    
                    {{-- Tabs com Design Moderno --}}
                    <div x-data="{ activeTab: 'dados-pessoais' }" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        
                        {{-- Navegação de Tabs Estilizada --}}
                        <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <nav class="flex overflow-x-auto">
                                <button @click="activeTab = 'dados-pessoais'" 
                                        :class="activeTab === 'dados-pessoais' ? 'border-[#003366] text-[#003366] bg-blue-50/50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                                        class="flex items-center gap-2.5 px-6 py-4 border-b-3 font-semibold text-sm transition-all duration-200 whitespace-nowrap">
                                    <i class="fa-solid fa-user-circle text-lg"></i>
                                    <span>Dados Pessoais</span>
                                </button>
                                <button @click="activeTab = 'endereco'" 
                                        :class="activeTab === 'endereco' ? 'border-[#003366] text-[#003366] bg-blue-50/50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                                        class="flex items-center gap-2.5 px-6 py-4 border-b-3 font-semibold text-sm transition-all duration-200 whitespace-nowrap">
                                    <i class="fa-solid fa-map-location-dot text-lg"></i>
                                    <span>Endereço</span>
                                </button>
                                <button @click="activeTab = 'preferencias'" 
                                        :class="activeTab === 'preferencias' ? 'border-[#003366] text-[#003366] bg-blue-50/50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                                        class="flex items-center gap-2.5 px-6 py-4 border-b-3 font-semibold text-sm transition-all duration-200 whitespace-nowrap">
                                    <i class="fa-solid fa-sliders text-lg"></i>
                                    <span>Preferências</span>
                                </button>
                                <button @click="activeTab = 'privacidade'" 
                                        :class="activeTab === 'privacidade' ? 'border-[#003366] text-[#003366] bg-blue-50/50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                                        class="flex items-center gap-2.5 px-6 py-4 border-b-3 font-semibold text-sm transition-all duration-200 whitespace-nowrap">
                                    <i class="fa-solid fa-lock text-lg"></i>
                                    <span>Privacidade</span>
                                </button>
                            </nav>
                        </div>

                        {{-- Conteúdo dos Tabs --}}
                        <div class="p-8">
                            <form action="{{ route('aluno.perfil.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                {{-- ========== TAB: DADOS PESSOAIS ========== --}}
                                <div x-show="activeTab === 'dados-pessoais'" x-transition class="space-y-8">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                                <i class="fa-solid fa-id-card text-[#003366]"></i>
                                            </div>
                                            Informações Pessoais
                                        </h3>
                                        <p class="text-gray-600 ml-13">Mantenha seus dados sempre atualizados</p>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {{-- Nome Completo --}}
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-user text-[#003366]"></i>
                                                Nome Completo *
                                            </label>
                                            <input type="text" 
                                                   name="name" 
                                                   value="{{ old('name', $aluno->name) }}" 
                                                   required
                                                   class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Email --}}
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-envelope text-[#003366]"></i>
                                                E-mail *
                                            </label>
                                            <input type="email" 
                                                   name="email" 
                                                   value="{{ old('email', $aluno->email) }}" 
                                                   required
                                                   class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Telefone --}}
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-phone text-[#003366]"></i>
                                                Telefone / WhatsApp
                                            </label>
                                            <input type="text" 
                                                   name="telefone" 
                                                   value="{{ old('telefone', $aluno->telefone) }}" 
                                                   x-mask="(99) 99999-9999"
                                                   class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- CPF --}}
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-id-card text-[#003366]"></i>
                                                CPF
                                            </label>
                                            <input type="text" 
                                                   name="cpf" 
                                                   value="{{ old('cpf', $aluno->cpf) }}" 
                                                   x-mask="999.999.999-99"
                                                   class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Data de Nascimento --}}
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-cake-candles text-[#003366]"></i>
                                                Data de Nascimento
                                            </label>
                                            <input type="date" 
                                                   name="data_nasc" 
                                                   value="{{ old('data_nasc', $aluno->data_nasc ? $aluno->data_nasc->format('Y-m-d') : '') }}"
                                                   max="{{ now()->format('Y-m-d') }}"
                                                   class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Gênero --}}
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-venus-mars text-[#003366]"></i>
                                                Gênero
                                            </label>
                                            <select name="genero" 
                                                    class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                                <option value="">Selecione</option>
                                                <option value="masculino" {{ old('genero', $aluno->genero) == 'masculino' ? 'selected' : '' }}>Masculino</option>
                                                <option value="feminino" {{ old('genero', $aluno->genero) == 'feminino' ? 'selected' : '' }}>Feminino</option>
                                                <option value="outro" {{ old('genero', $aluno->genero) == 'outro' ? 'selected' : '' }}>Outro</option>
                                                <option value="prefiro_nao_informar" {{ old('genero', $aluno->genero) == 'prefiro_nao_informar' ? 'selected' : '' }}>Prefiro não informar</option>
                                            </select>
                                        </div>

                                        {{-- Profissão --}}
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-briefcase text-[#003366]"></i>
                                                Profissão
                                            </label>
                                            <input type="text" 
                                                   name="profissao" 
                                                   value="{{ old('profissao', $aluno->profissao) }}"
                                                   class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Escolaridade --}}
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-graduation-cap text-[#003366]"></i>
                                                Escolaridade
                                            </label>
                                            <select name="escolaridade" 
                                                    class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow">
                                                <option value="">Selecione</option>
                                                <option value="fundamental_incompleto" {{ old('escolaridade', $aluno->escolaridade) == 'fundamental_incompleto' ? 'selected' : '' }}>Fundamental Incompleto</option>
                                                <option value="fundamental_completo" {{ old('escolaridade', $aluno->escolaridade) == 'fundamental_completo' ? 'selected' : '' }}>Fundamental Completo</option>
                                                <option value="medio_incompleto" {{ old('escolaridade', $aluno->escolaridade) == 'medio_incompleto' ? 'selected' : '' }}>Médio Incompleto</option>
                                                <option value="medio_completo" {{ old('escolaridade', $aluno->escolaridade) == 'medio_completo' ? 'selected' : '' }}>Médio Completo</option>
                                                <option value="superior_incompleto" {{ old('escolaridade', $aluno->escolaridade) == 'superior_incompleto' ? 'selected' : '' }}>Superior Incompleto</option>
                                                <option value="superior_completo" {{ old('escolaridade', $aluno->escolaridade) == 'superior_completo' ? 'selected' : '' }}>Superior Completo</option>
                                                <option value="pos_graduacao" {{ old('escolaridade', $aluno->escolaridade) == 'pos_graduacao' ? 'selected' : '' }}>Pós-Graduação</option>
                                                <option value="mestrado" {{ old('escolaridade', $aluno->escolaridade) == 'mestrado' ? 'selected' : '' }}>Mestrado</option>
                                                <option value="doutorado" {{ old('escolaridade', $aluno->escolaridade) == 'doutorado' ? 'selected' : '' }}>Doutorado</option>
                                            </select>
                                        </div>

                                        {{-- Biografia --}}
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-quote-left text-[#003366]"></i>
                                                Biografia (opcional)
                                            </label>
                                            <textarea name="bio" 
                                                      rows="4" 
                                                      maxlength="500"
                                                      placeholder="Conte um pouco sobre você, suas aspirações e objetivos..."
                                                      class="w-full rounded-xl border-gray-300 focus:border-[#003366] focus:ring-2 focus:ring-[#003366]/20 transition shadow-sm hover:shadow resize-none">{{ old('bio', $aluno->bio) }}</textarea>
                                            <p class="text-xs text-gray-500 mt-2">Máximo 500 caracteres</p>
                                        </div>

                                        {{-- Foto de Perfil --}}
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                                <i class="fa-solid fa-camera text-[#003366]"></i>
                                                Foto de Perfil
                                            </label>
                                            <div class="flex items-center gap-6">
                                                @if($aluno->foto)
                                                    <img src="{{ asset('storage/' . $aluno->foto) }}" 
                                                         alt="Prévia" 
                                                         class="w-24 h-24 rounded-2xl object-cover border-4 border-gray-200 shadow-lg">
                                                @else
                                                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center border-4 border-gray-200 shadow-lg">
                                                        <i class="fa-solid fa-user text-3xl text-gray-400"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-1">
                                                    <input type="file" 
                                                           name="foto" 
                                                           accept="image/*"
                                                           class="w-full text-sm text-gray-600 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[#003366] hover:file:bg-blue-100 file:cursor-pointer file:transition-all">
                                                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, WEBP. Máximo 5MB</p>
                                                    
                                                    @if($aluno->foto)
                                                        <button type="button" 
                                                                onclick="if(confirm('Deseja remover sua foto de perfil?')) { document.getElementById('form-deletar-foto').submit(); }"
                                                                class="mt-3 text-sm text-red-600 hover:text-red-700 font-medium flex items-center gap-2 transition-colors">
                                                            <i class="fa-solid fa-trash"></i>
                                                            Remover foto atual
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ========== TAB: ENDEREÇO ========== --}}
                                <div x-show="activeTab === 'endereco'" x-transition class="space-y-8">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                                            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                                <i class="fa-solid fa-location-dot text-emerald-600"></i>
                                            </div>
                                            Endereço de Correspondência
                                        </h3>
                                        <p class="text-gray-600 ml-13">Preencha o CEP para autocompletar</p>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                                        {{-- CEP --}}
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                                <i class="fa-solid fa-map-pin text-emerald-600"></i>
                                                CEP
                                            </label>
                                            <input type="text" 
                                                   name="cep" 
                                                   id="cep" 
                                                   value="{{ old('cep', $aluno->cep) }}" 
                                                   x-mask="99999-999"
                                                   class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition shadow-sm hover:shadow">
                                            <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                                <i class="fa-solid fa-info-circle"></i>
                                                Autocompletar endereço
                                            </p>
                                        </div>
                                        
                                        {{-- Logradouro --}}
                                        <div class="md:col-span-3">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Rua / Avenida</label>
                                            <input type="text" 
                                                   name="endereco" 
                                                   id="logradouro" 
                                                   value="{{ old('endereco', $aluno->endereco) }}"
                                                   class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Número --}}
                                        <div class="md:col-span-1">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Número</label>
                                            <input type="text" 
                                                   name="numero" 
                                                   value="{{ old('numero', $aluno->numero) }}"
                                                   class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Complemento --}}
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Complemento</label>
                                            <input type="text" 
                                                   name="complemento" 
                                                   value="{{ old('complemento', $aluno->complemento) }}"
                                                   placeholder="Apto, Bloco, Casa..."
                                                   class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Bairro --}}
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Bairro</label>
                                            <input type="text" 
                                                   name="bairro" 
                                                   id="bairro" 
                                                   value="{{ old('bairro', $aluno->bairro) }}"
                                                   class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition shadow-sm hover:shadow">
                                        </div>

                                        {{-- Cidade --}}
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cidade</label>
                                            <input type="text" 
                                                   name="cidade" 
                                                   id="cidade" 
                                                   value="{{ old('cidade', $aluno->cidade) }}"
                                                   class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition shadow-sm hover:shadow">
                                        </div>

                                        <div class="md:col-span-6">
                                            <input type="hidden" name="estado" id="uf" value="{{ old('estado', $aluno->estado) }}">
                                        </div>
                                    </div>
                                </div>

                                {{-- ========== TAB: PREFERÊNCIAS ========== --}}
                                <div x-show="activeTab === 'preferencias'" x-transition class="space-y-8">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                                            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                                <i class="fa-solid fa-bell text-purple-600"></i>
                                            </div>
                                            Preferências e Notificações
                                        </h3>
                                        <p class="text-gray-600 ml-13">Configure como deseja receber informações</p>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        {{-- Newsletter --}}
                                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6 hover:shadow-md transition-all duration-300">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-start gap-4 flex-1">
                                                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <i class="fa-solid fa-envelope-open-text text-blue-600 text-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-800 mb-1">Newsletter Educacional</h4>
                                                        <p class="text-sm text-gray-600">
                                                            Receba novidades, dicas de estudos e promoções exclusivas
                                                        </p>
                                                    </div>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer ml-4">
                                                    <input type="checkbox" 
                                                           name="receber_newsletter" 
                                                           value="1" 
                                                           {{ old('receber_newsletter', $aluno->receber_newsletter) ? 'checked' : '' }}
                                                           class="sr-only peer">
                                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- Notificações Email --}}
                                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-200 rounded-2xl p-6 hover:shadow-md transition-all duration-300">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-start gap-4 flex-1">
                                                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <i class="fa-solid fa-bell-concierge text-emerald-600 text-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-800 mb-1">Notificações por E-mail</h4>
                                                        <p class="text-sm text-gray-600">
                                                            Alertas sobre novos cursos, atualizações e lembretes importantes
                                                        </p>
                                                    </div>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer ml-4">
                                                    <input type="checkbox" 
                                                           name="receber_notificacoes_email" 
                                                           value="1" 
                                                           {{ old('receber_notificacoes_email', $aluno->receber_notificacoes_email) ? 'checked' : '' }}
                                                           class="sr-only peer">
                                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- Perfil Público --}}
                                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-2xl p-6 hover:shadow-md transition-all duration-300">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-start gap-4 flex-1">
                                                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <i class="fa-solid fa-users text-purple-600 text-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-800 mb-1">Perfil Público</h4>
                                                        <p class="text-sm text-gray-600">
                                                            Permitir que outros alunos vejam seu perfil e conquistas
                                                        </p>
                                                    </div>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer ml-4">
                                                    <input type="checkbox" 
                                                           name="perfil_publico" 
                                                           value="1" 
                                                           {{ old('perfil_publico', $aluno->perfil_publico) ? 'checked' : '' }}
                                                           class="sr-only peer">
                                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ========== TAB: PRIVACIDADE ========== --}}
                                <div x-show="activeTab === 'privacidade'" x-transition class="space-y-8">
                                    <div>
                                        <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                                            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                                                <i class="fa-solid fa-shield text-red-600"></i>
                                            </div>
                                            Privacidade e Dados
                                        </h3>
                                        <p class="text-gray-600 ml-13">Gerencie seus dados e privacidade</p>
                                    </div>
                                    
                                    <div class="space-y-6">
                                        {{-- Exportar Dados --}}
                                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-2xl p-8 hover:shadow-lg transition-all duration-300">
                                            <div class="flex items-start gap-6">
                                                <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-md">
                                                    <i class="fa-solid fa-cloud-arrow-down text-blue-600 text-2xl"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-xl font-bold text-gray-800 mb-2">Exportar Meus Dados</h4>
                                                    <p class="text-gray-600 mb-4">
                                                        Baixe uma cópia completa de todas as suas informações pessoais, 
                                                        matrículas, certificados e histórico acadêmico em formato JSON.
                                                    </p>
                                                    <a href="{{ route('aluno.perfil.exportar-dados') }}" 
                                                       class="inline-flex items-center gap-3 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all duration-300 font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                                                        <i class="fa-solid fa-download"></i>
                                                        Baixar Dados Completos
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Zona de Perigo --}}
                                        <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-300 rounded-2xl p-8 hover:shadow-lg transition-all duration-300">
                                            <div class="flex items-start gap-6">
                                                <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-md">
                                                    <i class="fa-solid fa-triangle-exclamation text-red-600 text-2xl"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-xl font-bold text-red-800 mb-2">Zona de Perigo</h4>
                                                    <p class="text-gray-700 mb-4">
                                                        Você terá <strong>30 dias</strong> para cancelar após solicitar a exclusão. 
                                                        Após esse período, todos os seus dados serão <strong>permanentemente excluídos</strong> 
                                                        e não poderão ser recuperados.
                                                    </p>
                                                    <button type="button" 
                                                            onclick="document.getElementById('modal-exclusao').classList.remove('hidden')"
                                                            class="inline-flex items-center gap-3 px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all duration-300 font-semibold shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                                                        <i class="fa-solid fa-user-slash"></i>
                                                        Solicitar Exclusão da Conta
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Informações de Segurança --}}
                                        <div class="bg-gradient-to-br from-slate-50 to-gray-100 border-2 border-gray-200 rounded-2xl p-8">
                                            <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-3">
                                                <i class="fa-solid fa-shield-halved text-[#003366]"></i>
                                                Como Protegemos Seus Dados
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="flex items-start gap-3 p-4 bg-white rounded-xl">
                                                    <i class="fa-solid fa-lock text-emerald-500 mt-1"></i>
                                                    <div>
                                                        <h5 class="font-semibold text-gray-800 mb-1">Criptografia Total</h5>
                                                        <p class="text-sm text-gray-600">Dados criptografados end-to-end</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-start gap-3 p-4 bg-white rounded-xl">
                                                    <i class="fa-solid fa-user-shield text-blue-500 mt-1"></i>
                                                    <div>
                                                        <h5 class="font-semibold text-gray-800 mb-1">Privacidade Garantida</h5>
                                                        <p class="text-sm text-gray-600">Sem compartilhamento com terceiros</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-start gap-3 p-4 bg-white rounded-xl">
                                                    <i class="fa-solid fa-gavel text-purple-500 mt-1"></i>
                                                    <div>
                                                        <h5 class="font-semibold text-gray-800 mb-1">Conformidade Legal</h5>
                                                        <p class="text-sm text-gray-600">LGPD e GDPR compliance</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-start gap-3 p-4 bg-white rounded-xl">
                                                    <i class="fa-solid fa-hand-holding-heart text-red-500 mt-1"></i>
                                                    <div>
                                                        <h5 class="font-semibold text-gray-800 mb-1">Controle Total</h5>
                                                        <p class="text-sm text-gray-600">Você decide o que compartilhar</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Botão de Salvar (exceto tab Privacidade) --}}
                                <div x-show="activeTab !== 'privacidade'" 
                                     class="flex items-center justify-between pt-8 mt-8 border-t-2 border-gray-200">
                                    <p class="text-sm text-gray-600 flex items-center gap-2">
                                        <i class="fa-solid fa-info-circle text-[#003366]"></i>
                                        <span>Campos marcados com <strong class="text-red-500">*</strong> são obrigatórios</span>
                                    </p>
                                    <button type="submit" 
                                            class="group relative bg-gradient-to-r from-[#003366] via-blue-700 to-indigo-700 hover:from-blue-800 hover:via-blue-900 hover:to-indigo-900 text-white font-bold py-4 px-10 rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                                        <span class="relative z-10 flex items-center gap-3">
                                            <i class="fa-solid fa-floppy-disk text-lg"></i>
                                            Salvar Alterações
                                        </span>
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-400 opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{-- MODAL DE EXCLUSÃO DE CONTA --}}
    {{-- ============================= --}}
    <div id="modal-exclusao" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8 transform transition-all">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                    <i class="fa-solid fa-exclamation-triangle text-red-600 text-4xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">Confirmar Exclusão da Conta</h3>
                <p class="text-gray-600">
                    Esta ação iniciará o processo de exclusão. Você terá <strong class="text-red-600">30 dias</strong> 
                    para cancelar antes da exclusão permanente de todos os seus dados.
                </p>
            </div>

            <form action="{{ route('aluno.perfil.solicitar-exclusao') }}" method="POST" class="space-y-5">
                @csrf
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Digite sua senha para confirmar
                    </label>
                    <input type="password" 
                           name="senha_confirmacao" 
                           required 
                           class="w-full rounded-xl border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Motivo da exclusão (opcional)
                    </label>
                    <textarea name="motivo" 
                              rows="3" 
                              class="w-full rounded-xl border-gray-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition resize-none"
                              placeholder="Ajude-nos a melhorar nossos serviços..."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" 
                            onclick="document.getElementById('modal-exclusao').classList.add('hidden')"
                            class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-all duration-300 font-semibold shadow-md hover:shadow-lg">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all duration-300 font-semibold shadow-md hover:shadow-lg">
                        Confirmar Exclusão
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Formulário oculto para deletar foto --}}
    @if($aluno->foto)
        <form id="form-deletar-foto" action="{{ route('aluno.perfil.deletar-foto') }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif

    {{-- ============================= --}}
    {{-- SCRIPTS --}}
    {{-- ============================= --}}
    @push('scripts')
    <script>
        // Busca automática de CEP com feedback visual
        document.getElementById('cep')?.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) return;
            
            const campos = ['logradouro', 'bairro', 'cidade', 'uf'];
            
            // Loading state com animação
            campos.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.value = '🔍 Buscando...';
                    el.classList.add('animate-pulse');
                }
            });
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    campos.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.classList.remove('animate-pulse');
                    });
                    
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('uf').value = data.uf || '';
                    } else {
                        campos.forEach(id => {
                            const el = document.getElementById(id);
                            if (el) el.value = '';
                        });
                        alert('❌ CEP não encontrado.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                    campos.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.value = '';
                            el.classList.remove('animate-pulse');
                        }
                    });
                    alert('⚠️ Erro ao buscar CEP. Verifique sua conexão.');
                });
        });

        // Fecha modal ao clicar fora
        document.getElementById('modal-exclusao')?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        // Preview da foto antes do upload
        document.querySelector('input[name="foto"]')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('⚠️ A imagem não pode ser maior que 5MB');
                    this.value = '';
                    return;
                }
                console.log('✅ Foto selecionada:', file.name);
            }
        });
    </script>
    @endpush
@endsection