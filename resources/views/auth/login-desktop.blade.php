<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CETS Portal do Aluno</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="h-full bg-slate-50">
    <div class="flex min-h-full">
        {{-- Lado Esquerdo: Branding --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-[#003366] to-[#001a33] relative overflow-hidden">
            <div class="relative z-10 flex flex-col justify-center px-16 w-full">
                {{-- Logo e Título --}}
                <div class="mb-12">
                    <div class="w-20 h-20 bg-white/10 rounded-3xl flex items-center justify-center mb-6 shadow-2xl backdrop-blur-md border border-white/20 animate-float">
                        <i class="fa-solid fa-book-bible text-white text-4xl"></i>
                    </div>
                    <h1 class="text-6xl font-black text-white mb-4 tracking-tight">CETS</h1>
                    <div class="h-1.5 w-24 bg-yellow-400 rounded-full mb-6"></div>
                    <p class="text-2xl text-blue-100 font-bold leading-relaxed">
                        Centro de Educação<br/>Teológica
                    </p>
                </div>
                
                {{-- Features --}}
                <div class="space-y-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fa-solid fa-video text-blue-300 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold">Aulas Online</p>
                            <p class="text-blue-200 text-sm">Estude de qualquer lugar</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fa-solid fa-certificate text-yellow-300 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold">Certificação Reconhecida</p>
                            <p class="text-blue-200 text-sm">Válida em todo território nacional</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fa-solid fa-users text-green-300 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white font-bold">Professores Qualificados</p>
                            <p class="text-blue-200 text-sm">Mestres e doutores em teologia</p>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Background Decorations --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <i class="fa-solid fa-cross text-white/5 text-[30rem] absolute -right-40 -top-40 rotate-12"></i>
                <i class="fa-solid fa-bible text-white/5 text-[20rem] absolute -left-20 -bottom-20 -rotate-12"></i>
                <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-[#000d1a] to-transparent opacity-60"></div>
            </div>
        </div>
        
        {{-- Lado Direito: Formulário de Login --}}
        <div class="flex-1 flex items-center justify-center px-8 py-12 lg:px-16">
            <div class="w-full max-w-md">
                {{-- Header Mobile (visível apenas em telas pequenas) --}}
                <div class="lg:hidden text-center mb-10">
                    <div class="w-16 h-16 bg-[#003366] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fa-solid fa-book-bible text-white text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-black text-[#003366]">CETS</h2>
                </div>
                
                {{-- Formulário --}}
                <div>
                    <div class="mb-10">
                        <h2 class="text-3xl font-black text-slate-800">Bem-vindo de volta!</h2>
                        <p class="text-slate-500 mt-2 font-medium">Entre com suas credenciais para acessar seu portal acadêmico.</p>
                    </div>
                    
                    {{-- Mensagens de Erro --}}
                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-red-800">Erro ao fazer login</p>
                                    <p class="text-xs text-red-600 mt-1">{{ $errors->first() }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        
                        {{-- CPF --}}
                        <div>
                            <label for="cpf" class="block text-xs font-black uppercase text-slate-600 mb-2 tracking-widest">
                                CPF
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-user text-slate-400"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="cpf"
                                    name="cpf" 
                                    value="{{ old('cpf') }}"
                                    placeholder="000.000.000-00"
                                    required
                                    autofocus
                                    class="w-full pl-12 pr-4 py-4 bg-white border-2 border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 text-slate-800 font-bold placeholder:text-slate-300 placeholder:font-normal transition-all"
                                >
                            </div>
                        </div>
                        
                        {{-- Senha --}}
                        <div>
                            <label for="password" class="block text-xs font-black uppercase text-slate-600 mb-2 tracking-widest">
                                Senha
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-lock text-slate-400"></i>
                                </div>
                                <input 
                                    type="password" 
                                    id="password"
                                    name="password" 
                                    placeholder="••••••••"
                                    required
                                    class="w-full pl-12 pr-4 py-4 bg-white border-2 border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 text-slate-800 font-bold placeholder:text-slate-300 placeholder:font-normal transition-all"
                                >
                            </div>
                        </div>
                        
                        {{-- Lembrar-me e Esqueci a senha --}}
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="remember"
                                    class="w-4 h-4 rounded border-slate-300 text-[#003366] focus:ring-blue-500/20"
                                >
                                <span class="ml-2 text-sm font-bold text-slate-600">Lembrar-me</span>
                            </label>
                            
                            <a href="{{ route('password.request') }}" class="text-sm font-bold text-[#003366] hover:text-[#002244] border-b-2 border-transparent hover:border-[#003366] transition-all">
                                Esqueci minha senha
                            </a>
                        </div>
                        
                        {{-- Botão de Login --}}
                        <button 
                            type="submit"
                            class="w-full bg-[#003366] text-white py-4 rounded-xl font-black text-sm uppercase tracking-[0.2em] shadow-xl hover:bg-[#002244] hover:shadow-2xl active:scale-[0.98] transition-all"
                        >
                            Entrar no Portal
                        </button>
                    </form>
                    
                    {{-- Divider --}}
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="px-4 bg-slate-50 text-slate-500 font-bold uppercase tracking-widest">Primeiro Acesso?</span>
                        </div>
                    </div>
                    
                    {{-- Link para Cadastro --}}
                    <div class="text-center">
                        <p class="text-sm text-slate-600">
                            Ainda não tem conta? 
                            <a href="{{ route('register') }}" class="font-bold text-[#003366] hover:text-[#002244] border-b-2 border-transparent hover:border-[#003366] transition-all ml-1">
                                Entre em contato com a secretaria
                            </a>
                        </p>
                    </div>
                    
                    {{-- Footer Info --}}
                    <div class="mt-12 pt-8 border-t border-slate-200 text-center">
                        <div class="flex items-center justify-center space-x-2 text-slate-400 mb-2">
                            <i class="fa-solid fa-shield-halved text-sm"></i>
                            <p class="text-xs font-black uppercase tracking-widest">Conexão Segura</p>
                        </div>
                        <p class="text-xs text-slate-400">© 2026 CETS - Todos os direitos reservados</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
