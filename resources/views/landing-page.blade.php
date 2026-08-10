<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $dados->hero_titulo_principal ?? 'CETSADP' }} - Centro Educacional Teológico</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#003D7A',
                        secondary: '#D62828',
                        accent: '#F77F00',
                    }
                }
            }
        }
    </script>
    
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .animate-fade-in-up { animation: fadeInUp 0.8s ease-out forwards; }
        .animate-float { animation: float 3s ease-in-out infinite; }
        .gradient-text {
            background: linear-gradient(135deg, #F77F00, #D62828);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0, 61, 122, 0.2); }
        .glassmorphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hero-pattern {
            background-image: radial-gradient(circle at 20% 50%, rgba(247, 127, 0, 0.1) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(214, 40, 40, 0.1) 0%, transparent 50%);
        }
        .gradient-bg { background: linear-gradient(135deg, #003D7A 0%, #001F3F 100%); }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-800">

    <header class="fixed w-full top-0 z-50 bg-white/95 backdrop-blur-sm shadow-md transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('assets/images/cetsadp.png') }}" onerror="this.style.display='none'; document.getElementById('logo-text').style.display='block';" alt="CETSADP" class="h-12 w-auto">
                    <div id="logo-text" class="hidden sm:block leading-tight">
                        <span class="font-bold text-xl text-primary block">CETSADP</span>
                        <span class="text-xs text-gray-500 uppercase tracking-wider">Paracambi - RJ</span>
                    </div>
                </div>

                <nav class="hidden md:flex space-x-8">
                    <a href="#inicio" class="text-gray-700 hover:text-primary font-medium transition">Início</a>
                    <a href="#sobre" class="text-gray-700 hover:text-primary font-medium transition">Sobre</a>
                    <a href="#cursos" class="text-gray-700 hover:text-primary font-medium transition">Cursos</a>
                    <a href="#diferenciais" class="text-gray-700 hover:text-primary font-medium transition">Diferenciais</a>
                    <a href="#contato" class="text-gray-700 hover:text-primary font-medium transition">Contato</a>
                </nav>

                <div class="hidden md:block">
                    <a href="/portal/login" class="px-6 py-2.5 bg-primary text-white rounded-full font-semibold shadow-lg hover:bg-secondary transition transform hover:scale-105 duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i>Portal do Aluno
                    </a>
                </div>

                <button id="mobile-menu-btn" class="md:hidden text-gray-700 hover:text-primary focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 shadow-lg">
            <div class="px-4 py-3 space-y-3">
                <a href="#inicio" class="block py-2 text-gray-700 hover:text-primary font-medium">Início</a>
                <a href="#sobre" class="block py-2 text-gray-700 hover:text-primary font-medium">Sobre</a>
                <a href="#cursos" class="block py-2 text-gray-700 hover:text-primary font-medium">Cursos</a>
                <a href="#diferenciais" class="block py-2 text-gray-700 hover:text-primary font-medium">Diferenciais</a>
                <a href="#contato" class="block py-2 text-gray-700 hover:text-primary font-medium">Contato</a>
                <a href="/portal/login" class="block py-3 px-4 bg-primary text-white rounded-lg text-center font-semibold mt-4">
                    <i class="fas fa-sign-in-alt mr-2"></i>Portal do Aluno
                </a>
            </div>
        </div>
    </header>

    <section id="inicio" class="pt-32 pb-20 lg:pt-40 lg:pb-32 overflow-hidden min-h-screen flex items-center bg-gray-50 relative">
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-blue-50 to-transparent opacity-60 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                
                <div class="space-y-6 animate-fade-in-up text-left">
                    <div class="inline-block px-4 py-1 rounded-full bg-blue-100 border border-blue-200 text-primary font-bold text-sm mb-2 shadow-sm">
                        🎓 Matrículas Abertas {{ date('Y') }}
                    </div>
                    
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight text-gray-900">
                        {{ $dados->hero_titulo_principal ?? 'Transforme Seu' }} 
                        <span class="gradient-text">{{ $dados->hero_destaque_1 ?? 'Chamado' }}</span> em 
                        <span class="gradient-text">{{ $dados->hero_destaque_2 ?? 'Ministério' }}</span>
                    </h1>
                    
                    <p class="text-lg md:text-xl text-gray-600 leading-relaxed max-w-xl">
                        {{ $dados->hero_subtitulo ?? 'Formação teológica de excelência...' }}
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <a href="{{ $dados->hero_botao_link ?? '/admin/register' }}" class="px-8 py-4 bg-accent text-white rounded-full font-bold text-lg shadow-xl hover:bg-orange-600 transition transform hover:scale-105 text-center flex items-center justify-center">
                            <i class="fas fa-rocket mr-2"></i> {{ $dados->hero_botao_texto ?? 'Comece Agora' }}
                        </a>
                        <a href="#cursos" class="px-8 py-4 bg-white text-primary border-2 border-gray-100 rounded-full font-bold text-lg hover:border-primary hover:bg-blue-50 transition text-center flex items-center justify-center shadow-sm">
                            <i class="fas fa-book-open mr-2"></i>Ver Cursos
                        </a>
                    </div>
                </div>

                <div class="relative hidden lg:block animate-float">
                    <div class="absolute inset-0 bg-gradient-to-r from-accent to-secondary rounded-3xl blur-2xl opacity-20 transform rotate-6"></div>
                    
                    @if(!empty($dados->hero_imagem))
                        <img src="{{ asset('storage/' . $dados->hero_imagem) }}" alt="Hero" class="relative rounded-3xl shadow-2xl object-cover w-full h-[550px] border-4 border-white">
                    @else
                        <img src="{{ asset('assets/images/estudo-biblico.jpg') }}" onerror="this.src='https://placehold.co/600x800/1e293b/FFF?text=Imagem+Estudo'" alt="Hero Padrão" class="relative rounded-3xl shadow-2xl object-cover w-full h-[550px] border-4 border-white">
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="sobre" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    {{ $dados->sobre_titulo ?? 'Quem Somos' }}
                </h2>
                <div class="w-24 h-1 bg-gradient-to-r from-accent to-secondary mx-auto rounded-full"></div>
            </div>

            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="space-y-6">
                    <p class="text-lg text-gray-700 leading-relaxed whitespace-pre-line">
                        {{ $dados->sobre_texto ?? 'O CETSADP é o Centro Educacional...' }}
                    </p>
                    
                    @if(!empty($dados->sobre_diferenciais))
                    <div class="grid sm:grid-cols-2 gap-4 pt-6">
                        @foreach($dados->sobre_diferenciais as $diferencial)
                        <div class="flex items-start space-x-3 p-4 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                            <div class="bg-primary/10 p-2 rounded-lg">
                                <i class="{{ $diferencial['icone'] ?? 'fas fa-check' }} text-primary text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $diferencial['titulo'] }}</h4>
                                <p class="text-xs text-gray-600 mt-1">{{ $diferencial['descricao'] ?? '' }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="relative">
                    <div class="absolute -inset-4 bg-gradient-to-r from-accent to-secondary rounded-3xl blur-2xl opacity-20"></div>
                    @if(!empty($dados->sobre_imagem))
                        <img src="{{ asset('storage/' . $dados->sobre_imagem) }}" alt="Sobre" class="relative rounded-2xl shadow-2xl w-full h-[500px] object-cover">
                    @else
                        <img src="https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=800&q=80" alt="Igreja" class="relative rounded-2xl shadow-2xl w-full h-[500px] object-cover">
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="cursos" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    {{ $dados->cursos_titulo ?? 'Nossos Cursos' }}
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    {{ $dados->cursos_descricao ?? 'Escolha a formação ideal para seu chamado.' }}
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @if(!empty($dados->cursos_lista))
                    @foreach($dados->cursos_lista as $curso)
                    <div class="card-hover bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 flex flex-col h-full">
                        <div class="relative h-56 overflow-hidden group">
                            @if(!empty($curso['imagem']))
                                <img src="{{ asset('storage/' . $curso['imagem']) }}" alt="{{ $curso['titulo'] }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            @else
                                <img src="https://placehold.co/600x400/003D7A/FFF?text={{ urlencode($curso['titulo']) }}" class="w-full h-full object-cover">
                            @endif
                            
                            @php
                                $badgeColor = match($curso['nivel'] ?? 'INICIANTE') {
                                    'INICIANTE' => 'bg-accent',
                                    'INTERMEDIÁRIO' => 'bg-primary',
                                    'AVANÇADO' => 'bg-secondary',
                                    default => 'bg-gray-500'
                                };
                            @endphp
                            <div class="absolute top-4 left-4 {{ $badgeColor }} text-white px-4 py-1 rounded-full text-xs font-bold shadow-lg uppercase tracking-wide">
                                {{ $curso['nivel'] ?? 'LIVRE' }}
                            </div>
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">{{ $curso['titulo'] }}</h3>
                            <p class="text-gray-600 mb-6 leading-relaxed flex-1 text-sm">
                                {{ $curso['resumo'] }}
                            </p>
                            <div class="space-y-3 mb-6 pt-4 border-t border-gray-100">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-clock text-accent w-6 text-center mr-2"></i>
                                    <span>{{ $curso['duracao'] ?? 'Indefinido' }}</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-book text-accent w-6 text-center mr-2"></i>
                                    <span>{{ $curso['modalidade'] ?? 'Online' }}</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-certificate text-accent w-6 text-center mr-2"></i>
                                    <span>{{ $curso['certificado'] ?? 'Sim' }}</span>
                                </div>
                            </div>
                            <a href="{{ $curso['link'] ?? '/admin/register' }}" class="block w-full text-center py-3 bg-primary text-white font-bold rounded-lg hover:bg-secondary transition shadow-md">
                                Inscrever-se
                            </a>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-center col-span-3 text-gray-500">Nenhum curso cadastrado no momento.</p>
                @endif
            </div>
        </div>
    </section>

    <section id="diferenciais" class="py-20 gradient-bg text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-accent opacity-10 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-secondary opacity-10 rounded-full blur-3xl transform -translate-x-1/3 translate-y-1/3"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">{{ $dados->vantagens_titulo ?? 'Diferenciais' }}</h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @if(!empty($dados->vantagens_lista))
                    @foreach($dados->vantagens_lista as $vantagem)
                    <div class="glassmorphism p-8 rounded-2xl hover:bg-white/10 transition group">
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center mb-6 text-2xl group-hover:scale-110 transition duration-300">
                            <i class="{{ $vantagem['icone'] ?? 'fas fa-star' }} text-accent"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3">{{ $vantagem['titulo'] }}</h3>
                        <p class="text-gray-300 text-sm leading-relaxed">{{ $vantagem['descricao'] }}</p>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <section id="faq" class="py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $dados->faq_titulo ?? 'FAQ' }}</h2>
            </div>

            <div class="space-y-4">
                @if(!empty($dados->faq_lista))
                    @foreach($dados->faq_lista as $faq)
                    <details class="group bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex items-center justify-between cursor-pointer">
                            <span class="text-lg font-semibold text-gray-900">{{ $faq['pergunta'] }}</span>
                            <span class="flex-shrink-0 ml-4 bg-gray-100 rounded-full p-2 transition group-open:bg-accent group-open:text-white">
                                <i class="fas fa-plus text-primary group-open:hidden"></i>
                                <i class="fas fa-minus hidden group-open:block"></i>
                            </span>
                        </summary>
                        <p class="mt-4 text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                            {{ $faq['resposta'] }}
                        </p>
                    </details>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <section id="contato" class="py-20 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $dados->contato_titulo ?? 'Contato' }}</h2>
                <p class="text-gray-600">{{ $dados->contato_descricao ?? '' }}</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-12">
                <div class="bg-gradient-to-br from-primary to-blue-900 text-white p-10 rounded-2xl shadow-xl flex flex-col justify-between">
                    <div>
                        <h3 class="text-2xl font-bold mb-6">Informações</h3>
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div class="bg-white/20 p-3 rounded-lg"><i class="fas fa-map-marker-alt text-xl"></i></div>
                                <div><h4 class="font-bold text-lg">Endereço</h4><p class="text-blue-100 text-sm">{{ $dados->endereco }}</p></div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="bg-white/20 p-3 rounded-lg"><i class="fas fa-phone-alt text-xl"></i></div>
                                <div><h4 class="font-bold text-lg">Telefone</h4><p class="text-blue-100 text-sm">{{ $dados->telefone }}</p></div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div class="bg-white/20 p-3 rounded-lg"><i class="fas fa-envelope text-xl"></i></div>
                                <div><h4 class="font-bold text-lg">E-mail</h4><p class="text-blue-100 text-sm">{{ $dados->email }}</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-12">
                        <h4 class="font-bold mb-4">Siga-nos</h4>
                        <div class="flex space-x-4">
                            @if($dados->link_facebook) <a href="https://{{ $dados->link_facebook }}" target="_blank" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-white hover:text-primary transition"><i class="fab fa-facebook-f"></i></a> @endif
                            @if($dados->link_instagram) <a href="https://{{ $dados->link_instagram }}" target="_blank" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-white hover:text-primary transition"><i class="fab fa-instagram"></i></a> @endif
                            @if($dados->link_youtube) <a href="https://{{ $dados->link_youtube }}" target="_blank" class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-white hover:text-primary transition"><i class="fab fa-youtube"></i></a> @endif
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                    <form action="#" class="space-y-6">
                        <input type="text" class="w-full px-4 py-3 rounded-lg bg-gray-50 border-gray-300" placeholder="Seu nome">
                        <input type="email" class="w-full px-4 py-3 rounded-lg bg-gray-50 border-gray-300" placeholder="seu@email.com">
                        <textarea rows="4" class="w-full px-4 py-3 rounded-lg bg-gray-50 border-gray-300" placeholder="Mensagem"></textarea>
                        <button type="button" class="w-full py-4 bg-accent text-white font-bold rounded-lg shadow-lg">Enviar Mensagem</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-300 py-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h3 class="text-2xl font-bold text-white mb-2">CETSADP</h3>
            <p class="text-sm text-gray-400">Centro Educacional, Teológico e Social</p>
            <div class="border-t border-gray-800 mt-8 pt-8 text-sm text-gray-500">
                &copy; {{ date('Y') }} CETSADP. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>