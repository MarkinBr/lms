<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Validação de Documentos - {{ config('instituicao.sigla') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Cabeçalho Oficial -->
    <div class="bg-white border-b-4 border-blue-900">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="{{ asset(config('instituicao.logo.path')) }}" alt="{{ config('instituicao.logo.alt') }}" class="h-20 w-20 object-contain">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-900 leading-tight">{{ config('instituicao.nome_completo') }}</h1>
                        <p class="text-sm text-gray-600 leading-tight">{{ config('instituicao.nome_extenso') }}</p>
                    </div>
                </div>
                <div class="text-right hidden md:block">
                    <p class="text-xs text-gray-600">{{ config('instituicao.sistema_validacao.nome') }}</p>
                    <p class="text-xs text-gray-600">Versão {{ config('instituicao.sistema_validacao.versao') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="bg-gray-50 border-b">
        <div class="container mx-auto px-4 py-2">
            <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-home mr-2"></i>
                <span>Início</span>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span class="text-blue-900 font-medium">Validação de Documentos</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Card Principal -->
        <div class="bg-white rounded-sm shadow-sm border border-gray-200">
            <!-- Cabeçalho do Sistema -->
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-8 py-6 border-b-2 border-yellow-400">
                <div class="flex items-center space-x-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-full p-4">
                        <i class="fas fa-shield-alt text-4xl text-white"></i>
                    </div>
                    <div class="text-white">
                        <h2 class="text-2xl font-bold mb-1">Sistema de Validação de Documentos</h2>
                        <p class="text-blue-100 text-sm">Verificação de Autenticidade Digital</p>
                    </div>
                </div>
            </div>

            <!-- Conteúdo -->
            <div class="p-8">
                <!-- Aviso Importante -->
                <div class="bg-blue-50 border-l-4 border-blue-900 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-900 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-900 font-semibold">Validação Oficial de Documentos</p>
                            <p class="text-sm text-blue-800 mt-1">Este sistema permite verificar a autenticidade de documentos emitidos pelo {{ config('instituicao.sigla') }} através do código de validação único presente em cada documento.</p>
                        </div>
                    </div>
                </div>

                <!-- Formulário -->
                <form action="{{ route('validacao.buscar') }}" method="POST" class="mb-8">
                    @csrf
                    
                    <div class="bg-gray-50 p-6 rounded border border-gray-200">
                        <label for="codigo" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-barcode mr-2 text-blue-900"></i>
                            Código de Validação do Documento
                        </label>
                        <input 
                            type="text" 
                            id="codigo" 
                            name="codigo"
                            placeholder="Digite o código (Ex: ABC12345-WXYZ)"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded focus:outline-none focus:border-blue-900 text-lg font-mono uppercase bg-white"
                            required
                            maxlength="50"
                        >
                        <p class="text-xs text-gray-600 mt-2 flex items-start">
                            <i class="fas fa-info-circle mr-2 mt-0.5 text-gray-500"></i>
                            <span>O código de validação é composto por 13 caracteres alfanuméricos e pode ser encontrado no rodapé do documento ou através do QR Code.</span>
                        </p>
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-blue-900 hover:bg-blue-800 text-white py-4 rounded font-bold text-base transition-colors mt-6 flex items-center justify-center space-x-3 shadow-sm"
                    >
                        <i class="fas fa-check-circle text-xl"></i>
                        <span>VALIDAR DOCUMENTO</span>
                    </button>
                </form>

                <!-- Instruções -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wide">
                        Instruções de Validação
                    </h3>
                    
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded border border-gray-200">
                            <div class="flex items-center mb-3">
                                <div class="bg-blue-900 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm mr-3">
                                    1
                                </div>
                                <h4 class="font-semibold text-gray-900 text-sm">Localize o Código</h4>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Identifique o código de validação impresso no documento original, geralmente localizado no rodapé ou próximo ao QR Code.
                            </p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded border border-gray-200">
                            <div class="flex items-center mb-3">
                                <div class="bg-blue-900 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm mr-3">
                                    2
                                </div>
                                <h4 class="font-semibold text-gray-900 text-sm">Insira no Sistema</h4>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Digite o código no campo acima ou utilize a câmera do seu dispositivo para escanear o QR Code presente no documento.
                            </p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded border border-gray-200">
                            <div class="flex items-center mb-3">
                                <div class="bg-blue-900 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm mr-3">
                                    3
                                </div>
                                <h4 class="font-semibold text-gray-900 text-sm">Verifique os Dados</h4>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Confira se as informações apresentadas pelo sistema correspondem exatamente aos dados impressos no documento físico.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Avisos de Segurança -->
                <div class="mt-6 space-y-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-700 text-lg mt-0.5 mr-3"></i>
                            <div>
                                <p class="text-sm font-semibold text-yellow-900 mb-1">Atenção Legal</p>
                                <p class="text-xs text-yellow-800 leading-relaxed">
                                    A falsificação, adulteração ou uso indevido de documentos constitui crime previsto nos artigos 297 e 304 do Código Penal Brasileiro, sujeito a penas de reclusão.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 border border-gray-300 rounded p-4">
                        <div class="flex items-start">
                            <i class="fas fa-headset text-gray-700 text-lg mt-0.5 mr-3"></i>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900 mb-2">Central de Atendimento</p>
                                <div class="grid md:grid-cols-2 gap-3 text-xs text-gray-700">
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope w-5 text-blue-900"></i>
                                        <a href="mailto:{{ config('instituicao.contatos.email') }}" class="hover:text-blue-900 font-medium">{{ config('instituicao.contatos.email') }}</a>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone w-5 text-blue-900"></i>
                                        <a href="tel:{{ str_replace(['(', ')', ' ', '-'], '', config('instituicao.contatos.telefone')) }}" class="hover:text-blue-900 font-medium">{{ config('instituicao.contatos.telefone') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="grid grid-cols-3 gap-4 mt-6">
            <div class="bg-white rounded border border-gray-200 p-4 text-center shadow-sm">
                <i class="fas fa-lock text-green-600 text-2xl mb-2"></i>
                <p class="text-lg font-bold text-gray-900">Seguro</p>
                <p class="text-xs text-gray-600">Criptografia de Dados</p>
            </div>
            <div class="bg-white rounded border border-gray-200 p-4 text-center shadow-sm">
                <i class="fas fa-bolt text-blue-600 text-2xl mb-2"></i>
                <p class="text-lg font-bold text-gray-900">Instantâneo</p>
                <p class="text-xs text-gray-600">Validação em Tempo Real</p>
            </div>
            <div class="bg-white rounded border border-gray-200 p-4 text-center shadow-sm">
                <i class="fas fa-clock text-gray-600 text-2xl mb-2"></i>
                <p class="text-lg font-bold text-gray-900">24/7</p>
                <p class="text-xs text-gray-600">Disponibilidade Total</p>
            </div>
        </div>
    </div>

    <!-- Rodapé Oficial -->
    <footer class="bg-gray-800 text-gray-300 mt-12 border-t-4 border-blue-900">
        <div class="container mx-auto px-4 py-6">
            <div class="text-center space-y-2">
                <p class="text-sm">
                    <i class="fas fa-map-marker-alt mr-2"></i>
                    {{ \App\Helpers\InstituicaoHelper::enderecoComCep() }}
                </p>
                <p class="text-sm">
                    <i class="fas fa-phone mr-2"></i>
                    {{ config('instituicao.contatos.telefone') }} | 
                    <i class="fas fa-envelope ml-3 mr-2"></i>
                    {{ config('instituicao.contatos.email') }}
                </p>
                <div class="border-t border-gray-700 pt-3 mt-3">
                    <p class="text-xs text-gray-400">
                        © {{ date('Y') }} {{ config('instituicao.nome_extenso') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Todos os direitos reservados. {{ config('instituicao.sistema_validacao.nome') }} v{{ config('instituicao.sistema_validacao.versao') }}
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Converte automaticamente para maiúscula
        document.getElementById('codigo').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>