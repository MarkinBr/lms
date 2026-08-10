<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site em Manutenção - CETSADP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $dados->cores['primary'] ?? '#003D7A' }}',
                        secondary: '{{ $dados->cores['secondary'] ?? '#D62828' }}',
                        accent: '{{ $dados->cores['accent'] ?? '#F77F00' }}',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .animate-pulse-slow {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, {{ $dados->cores['primary'] ?? '#003D7A' }} 0%, #001F3F 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-2xl">
        <!-- Ícone Animado -->
        <div class="mb-8 animate-float">
            <div class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-white/10 backdrop-blur-sm border-4 border-white/20">
                <i class="fas fa-tools text-6xl text-white"></i>
            </div>
        </div>

        <!-- Título -->
        <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
            Estamos em Manutenção
        </h1>

        <!-- Subtítulo -->
        <p class="text-xl text-gray-300 mb-8">
            Nosso site está passando por melhorias para servir você ainda melhor. 
            <br>Em breve estaremos de volta!
        </p>

        <!-- Loading Spinner -->
        <div class="flex justify-center items-center space-x-2 mb-12">
            <div class="w-3 h-3 bg-accent rounded-full animate-pulse-slow"></div>
            <div class="w-3 h-3 bg-accent rounded-full animate-pulse-slow" style="animation-delay: 0.2s;"></div>
            <div class="w-3 h-3 bg-accent rounded-full animate-pulse-slow" style="animation-delay: 0.4s;"></div>
        </div>

        <!-- Informações de Contato -->
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6">Entre em Contato</h2>
            <div class="space-y-4 text-left">
                @if($dados->whatsapp)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $dados->whatsapp) }}" target="_blank" class="flex items-center justify-center space-x-3 p-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition transform hover:scale-105">
                    <i class="fab fa-whatsapp text-2xl"></i>
                    <span class="font-semibold">Fale conosco pelo WhatsApp</span>
                </a>
                @endif

                @if($dados->email)
                <div class="flex items-center justify-center space-x-3 text-white">
                    <i class="fas fa-envelope text-accent"></i>
                    <span>{{ $dados->email }}</span>
                </div>
                @endif

                @if($dados->telefone)
                <div class="flex items-center justify-center space-x-3 text-white">
                    <i class="fas fa-phone text-accent"></i>
                    <span>{{ $dados->telefone }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Redes Sociais -->
        @if($dados->link_facebook || $dados->link_instagram || $dados->link_youtube)
        <div>
            <p class="text-gray-300 mb-4">Acompanhe nossas redes sociais:</p>
            <div class="flex justify-center space-x-4">
                @if($dados->link_facebook)
                <a href="https://{{ $dados->link_facebook }}" target="_blank" class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center hover:bg-white hover:text-primary transition transform hover:scale-110 text-white">
                    <i class="fab fa-facebook-f"></i>
                </a>
                @endif

                @if($dados->link_instagram)
                <a href="https://{{ $dados->link_instagram }}" target="_blank" class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center hover:bg-white hover:text-primary transition transform hover:scale-110 text-white">
                    <i class="fab fa-instagram"></i>
                </a>
                @endif

                @if($dados->link_youtube)
                <a href="https://{{ $dados->link_youtube }}" target="_blank" class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center hover:bg-white hover:text-primary transition transform hover:scale-110 text-white">
                    <i class="fab fa-youtube"></i>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Portal do Aluno -->
        <div class="mt-12 pt-8 border-t border-white/20">
            <p class="text-gray-300 mb-4">Já é nosso aluno?</p>
            <a href="/admin/login" class="inline-block px-8 py-4 bg-accent text-white font-bold rounded-full hover:bg-orange-600 transition transform hover:scale-105 shadow-xl">
                <i class="fas fa-sign-in-alt mr-2"></i>Acessar Portal do Aluno
            </a>
        </div>
    </div>

    <!-- Copyright -->
    <div class="fixed bottom-4 left-0 right-0 text-center text-gray-400 text-sm">
        &copy; {{ date('Y') }} CETSADP. Todos os direitos reservados.
    </div>
</body>
</html>
