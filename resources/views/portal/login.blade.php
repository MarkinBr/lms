{{-- 
    CETSADP — Login por Curso (Blade Template)
    
    Variáveis injetadas pelo middleware IdentifyCurso:
      $curso = [
        'id', 'nome', 'nome_curto', 'subdomain', 'icone', 'emoji',
        'cor_primaria', 'cor_secundaria', 'cor_accent', 'gradient',
        'bg_class', 'text_class', 'descricao', 'citacao'
      ]
    
    Variável do controller:
      $proximaAula = [
        'titulo', 'data', 'horario', 'professor', 'ao_vivo' (bool)
      ]
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $curso['nome'] }} — CETSADP</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'curso-primary': '{{ $curso["cor_primaria"] }}',
                        'curso-secondary': '{{ $curso["cor_secundaria"] }}',
                        'curso-accent': '{{ $curso["cor_accent"] }}',
                    },
                    fontFamily: {
                        heading: ['Cinzel', 'serif'],
                        body: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; }
        
        .glass {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
        }

        .pulse-live {
            animation: pulseLive 2s ease-in-out infinite;
        }
        @keyframes pulseLive {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
            50% { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
        }

        .pattern-overlay {
            position: absolute;
            inset: 0;
            opacity: 0.04;
            background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M20 5v12M14 11h12M20 23v12M14 29h12' stroke='%23ffffff' stroke-width='1' fill='none'/%3E%3C/svg%3E");
        }

        .slide-up {
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-field {
            transition: all 0.3s;
        }
        .input-field:focus {
            border-color: {{ $curso['cor_accent'] }};
            box-shadow: 0 0 0 3px {{ $curso['cor_accent'] }}33;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">
    <div class="min-h-screen flex flex-col lg:flex-row">
        
        {{-- ═══════════════════════════════════════════ --}}
        {{-- LADO ESQUERDO — Branding do Curso          --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="relative lg:w-[55%] bg-gradient-to-br {{ $curso['gradient'] }} text-white p-8 lg:p-16 flex flex-col justify-between overflow-hidden min-h-[40vh] lg:min-h-screen">
            
            {{-- Pattern decorativo --}}
            <div class="pattern-overlay"></div>
            
            {{-- Círculos decorativos --}}
            <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full bg-white/5 translate-y-1/2 -translate-x-1/2"></div>
            
            {{-- Header --}}
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
                        <i class="fas {{ $curso['icone'] }} text-lg" style="color: {{ $curso['cor_accent'] }}"></i>
                    </div>
                    <div>
                        <span class="font-heading text-sm tracking-widest opacity-70">CETSADP</span>
                        <p class="text-xs opacity-50">{{ $curso['subdomain'] }}</p>
                    </div>
                </div>
                
                <h1 class="font-heading text-3xl lg:text-5xl font-bold leading-tight mb-6 slide-up">
                    {{ $curso['nome'] }}
                </h1>
                
                <p class="text-base lg:text-lg opacity-80 max-w-lg leading-relaxed mb-8">
                    {{ $curso['descricao'] }}
                </p>
                
                <blockquote class="text-sm italic opacity-60 border-l-2 pl-4 max-w-md" style="border-color: {{ $curso['cor_accent'] }}">
                    {{ $curso['citacao'] }}
                </blockquote>
            </div>
            
            {{-- Card da Próxima Aula ao Vivo --}}
            @isset($proximaAula)
            <div class="relative z-10 mt-8 lg:mt-0">
                <div class="glass rounded-2xl p-6 max-w-md slide-up {{ $proximaAula['ao_vivo'] ? 'pulse-live' : '' }}">
                    <div class="flex items-center gap-2 mb-4">
                        @if($proximaAula['ao_vivo'])
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-red-400">AO VIVO AGORA</span>
                        @else
                            <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-green-300">PRÓXIMA AULA AO VIVO</span>
                        @endif
                    </div>
                    
                    <h3 class="text-lg font-semibold mb-3 leading-snug">
                        {{ $proximaAula['titulo'] }}
                    </h3>
                    
                    <div class="flex flex-wrap gap-4 text-sm opacity-70">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-calendar-alt"></i> {{ $proximaAula['data'] }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-clock"></i> {{ $proximaAula['horario'] }}
                        </span>
                    </div>
                    <p class="text-sm opacity-70 mt-2 flex items-center gap-1.5">
                        <i class="fas fa-chalkboard-teacher"></i> {{ $proximaAula['professor'] }}
                    </p>
                    
                    {{-- Countdown --}}
                    @unless($proximaAula['ao_vivo'])
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <p class="text-[10px] uppercase tracking-widest opacity-50 mb-2">COMEÇA EM</p>
                        <div class="flex gap-3" id="countdown">
                            <div class="text-center">
                                <span class="text-2xl font-bold" id="cd-days">--</span>
                                <p class="text-[9px] uppercase opacity-50">Dias</p>
                            </div>
                            <span class="text-2xl opacity-30">:</span>
                            <div class="text-center">
                                <span class="text-2xl font-bold" id="cd-hours">--</span>
                                <p class="text-[9px] uppercase opacity-50">Horas</p>
                            </div>
                            <span class="text-2xl opacity-30">:</span>
                            <div class="text-center">
                                <span class="text-2xl font-bold" id="cd-mins">--</span>
                                <p class="text-[9px] uppercase opacity-50">Min</p>
                            </div>
                            <span class="text-2xl opacity-30">:</span>
                            <div class="text-center">
                                <span class="text-2xl font-bold" id="cd-secs">--</span>
                                <p class="text-[9px] uppercase opacity-50">Seg</p>
                            </div>
                        </div>
                    </div>
                    @endunless
                </div>
            </div>
            @endisset
        </div>
        
        {{-- ═══════════════════════════════════════════ --}}
        {{-- LADO DIREITO — Formulário de Login          --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="lg:w-[45%] flex items-center justify-center p-8 lg:p-16 bg-white">
            <div class="w-full max-w-md slide-up">
                
                {{-- Mini Logo --}}
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" 
                             style="background: {{ $curso['cor_primaria'] }}">
                            <span class="text-white font-heading font-bold text-sm">C</span>
                        </div>
                        <span class="font-heading text-xl" style="color: {{ $curso['cor_primaria'] }}">CETSADP</span>
                    </div>
                    <h2 class="text-2xl font-heading font-bold text-gray-900">Acesse sua conta</h2>
                    <p class="text-sm text-gray-500 mt-2">{{ $curso['nome'] }}</p>
                </div>
                
                {{-- Erros de validação --}}
                @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
                    <div class="flex items-center gap-2 text-red-700 text-sm font-medium mb-1">
                        <i class="fas fa-exclamation-circle"></i>
                        Erro no login
                    </div>
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
                @endif
                
                {{-- Formulário --}}
                <form method="POST" action="{{ route('curso.login.post', ['curso' => $curso['id']]) }}" class="space-y-5">
                    @csrf
                    
                    {{-- CPF --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-widest mb-2">
                            CPF
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-id-card"></i>
                            </span>
                            <input 
                                type="text" 
                                name="cpf" 
                                id="cpf"
                                value="{{ old('cpf') }}"
                                placeholder="000.000.000-00"
                                maxlength="14"
                                required
                                class="input-field w-full pl-12 pr-4 py-4 bg-gray-50 rounded-xl text-sm border border-gray-200 focus:outline-none focus:bg-white"
                            >
                        </div>
                    </div>
                    
                    {{-- Senha --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-widest mb-2">
                            Senha
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                placeholder="••••••••"
                                required
                                class="input-field w-full pl-12 pr-12 py-4 bg-gray-50 rounded-xl text-sm border border-gray-200 focus:outline-none focus:bg-white"
                            >
                            <button type="button" onclick="togglePassword()" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    {{-- Lembrar-me --}}
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded accent-curso-primary">
                            <span class="text-sm text-gray-600">Lembrar-me</span>
                        </label>
                        <a href="{{ route('curso.forgot', ['curso' => $curso['id']]) }}" 
                           class="text-sm font-medium hover:underline"
                           style="color: {{ $curso['cor_primaria'] }}">
                            Esqueci minha senha
                        </a>
                    </div>
                    
                    {{-- Botão Entrar --}}
                    <button type="submit" 
                            class="w-full py-4 rounded-xl text-sm font-semibold text-white transition-all duration-300 hover:opacity-90 hover:scale-[1.01] shadow-lg hover:shadow-xl"
                            style="background: {{ $curso['cor_primaria'] }}">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Entrar na Plataforma
                    </button>
                </form>
                
                {{-- Divider --}}
                <div class="my-8 flex items-center gap-4">
                    <div class="flex-1 border-t border-gray-200"></div>
                    <span class="text-xs text-gray-400">ou</span>
                    <div class="flex-1 border-t border-gray-200"></div>
                </div>
                
                {{-- Registrar --}}
                <a href="{{ route('curso.register', ['curso' => $curso['id']]) }}"
                   class="block w-full py-4 rounded-xl text-sm font-semibold text-center border-2 transition-all duration-300 hover:scale-[1.01]"
                   style="border-color: {{ $curso['cor_primaria'] }}; color: {{ $curso['cor_primaria'] }}">
                    <i class="fas fa-user-plus mr-2"></i>
                    Realizar Matrícula
                </a>
                
                {{-- Footer --}}
                <div class="mt-10 text-center space-y-2">
                    <div class="flex items-center justify-center gap-2 text-xs text-gray-400">
                        <i class="fas fa-shield-halved"></i>
                        <span>Autenticação SSO JWT • Sessão segura</span>
                    </div>
                    <p class="text-[10px] text-gray-300">
                        CETSADP — Centro Educacional, Teológico e Social<br>
                        Assembleia de Deus de Paracambi
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ── Máscara de CPF ──
        document.getElementById('cpf').addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            e.target.value = v;
        });

        // ── Toggle senha ──
        function togglePassword() {
            const pw = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pw.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // ── Countdown ──
        @isset($proximaAula)
        @unless($proximaAula['ao_vivo'])
        (function() {
            const target = new Date('{{ $proximaAula["data_iso"] }}').getTime();
            function update() {
                const now = Date.now();
                const diff = Math.max(0, target - now);
                const d = Math.floor(diff / 86400000);
                const h = Math.floor((diff % 86400000) / 3600000);
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                document.getElementById('cd-days').textContent = String(d).padStart(2, '0');
                document.getElementById('cd-hours').textContent = String(h).padStart(2, '0');
                document.getElementById('cd-mins').textContent = String(m).padStart(2, '0');
                document.getElementById('cd-secs').textContent = String(s).padStart(2, '0');
            }
            update();
            setInterval(update, 1000);
        })();
        @endunless
        @endisset
    </script>
</body>
</html>
