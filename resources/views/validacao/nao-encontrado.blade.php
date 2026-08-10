<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Não Localizado - Sistema de Validação {{ config('instituicao.sigla') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Cabeçalho Oficial -->
    <div class="bg-white border-b-4 border-red-700">
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
                    <p class="text-xs text-gray-600">Secretaria Acadêmica</p>
                    <p class="text-xs text-gray-600">{{ config('instituicao.sistema_validacao.nome') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="bg-gray-50 border-b">
        <div class="container mx-auto px-4 py-2">
            <div class="flex items-center text-sm text-gray-600">
                <i class="fas fa-home mr-2"></i>
                <a href="{{ route('validacao.formulario') }}" class="hover:text-blue-900">Início</a>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span>Validação de Documentos</span>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span class="text-red-700 font-medium">Documento Não Localizado</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Card Principal -->
        <div class="bg-white rounded-sm shadow-sm border border-gray-200">
            <!-- Cabeçalho de Erro -->
            <div class="bg-gradient-to-r from-red-700 to-red-600 px-8 py-6 border-b-2 border-red-800">
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-full p-4">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-600"></i>
                    </div>
                    <div class="text-white">
                        <h2 class="text-2xl font-bold mb-1">Documento Não Localizado</h2>
                        <p class="text-red-100 text-sm">Código de validação não encontrado no sistema</p>
                    </div>
                </div>
            </div>

            <!-- Conteúdo -->
            <div class="p-8">
                <!-- Código Informado -->
                <div class="bg-red-50 border-l-4 border-red-700 p-5 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-barcode text-red-700 text-2xl mr-4 mt-1"></i>
                        <div>
                            <p class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-2">Código Pesquisado</p>
                            <p class="text-3xl font-mono font-bold text-red-900 tracking-wider">{{ $codigo }}</p>
                            <p class="text-xs text-red-600 mt-2">
                                <i class="fas fa-clock mr-1"></i>
                                Consulta realizada em {{ now()->format('d/m/Y') }} às {{ now()->format('H:i') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Alerta Principal -->
                <div class="bg-yellow-50 border border-yellow-300 rounded p-5 mb-6">
                    <div class="flex items-start">
                        <div class="bg-yellow-400 rounded-full p-2 mr-4">
                            <i class="fas fa-shield-exclamation text-yellow-900 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-bold text-yellow-900 mb-2 text-sm">ATENÇÃO: POSSÍVEL DOCUMENTO INVÁLIDO</p>
                            <p class="text-sm text-yellow-800 leading-relaxed">
                                O código informado não foi localizado em nossa base de dados oficial. Isso pode indicar que o documento não foi emitido pela nossa instituição ou que as informações foram adulteradas.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Possíveis Causas -->
                <div class="mb-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wide border-b pb-2">
                        Hipóteses para a Não Localização
                    </h3>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                            <div class="flex items-start">
                                <i class="fas fa-keyboard text-gray-600 text-lg mt-0.5 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm mb-1">Erro de Digitação</p>
                                    <p class="text-xs text-gray-600 leading-relaxed">O código pode ter sido digitado incorretamente. Verifique todos os caracteres.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                            <div class="flex items-start">
                                <i class="fas fa-ban text-gray-600 text-lg mt-0.5 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm mb-1">Documento Não Oficial</p>
                                    <p class="text-xs text-gray-600 leading-relaxed">O documento pode não ter sido emitido pelo {{ config('instituicao.sigla') }}.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                            <div class="flex items-start">
                                <i class="fas fa-times-circle text-gray-600 text-lg mt-0.5 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm mb-1">Cancelamento</p>
                                    <p class="text-xs text-gray-600 leading-relaxed">O documento pode ter sido cancelado ou invalidado pela instituição.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                            <div class="flex items-start">
                                <i class="fas fa-hourglass-end text-gray-600 text-lg mt-0.5 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm mb-1">Prazo Expirado</p>
                                    <p class="text-xs text-gray-600 leading-relaxed">O código pode ter expirado ou estar fora do período de validade.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aviso Legal -->
                <div class="bg-red-50 border-l-4 border-red-700 p-5 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-gavel text-red-700 text-xl mt-1 mr-4"></i>
                        <div>
                            <p class="font-bold text-red-900 mb-2 text-sm">ADVERTÊNCIA LEGAL</p>
                            <p class="text-sm text-red-800 mb-3 leading-relaxed">
                                A utilização, falsificação ou adulteração de documentos constitui crime previsto no Código Penal Brasileiro:
                            </p>
                            <ul class="space-y-2 text-xs text-red-800">
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span><strong>Art. 297</strong> - Falsificação de documento público (reclusão de 2 a 6 anos e multa)</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="mr-2">•</span>
                                    <span><strong>Art. 304</strong> - Uso de documento falso (reclusão de 2 a 6 anos e multa)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Procedimentos Recomendados -->
                <div class="bg-blue-50 border border-blue-200 rounded p-5 mb-6">
                    <h4 class="font-bold text-blue-900 mb-3 text-sm uppercase tracking-wide flex items-center">
                        <i class="fas fa-clipboard-check mr-2"></i>
                        Procedimentos Recomendados
                    </h4>
                    <div class="space-y-2 text-sm text-blue-900">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                            <p>Verifique cuidadosamente se o código foi digitado de forma correta, respeitando maiúsculas e minúsculas</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                            <p>Utilize o QR Code presente no documento original para evitar erros de digitação</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                            <p>Entre em contato com a Secretaria do {{ config('instituicao.sigla') }} para verificação manual do documento</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                            <p>Se recebeu o documento de terceiros, solicite confirmação junto à instituição emissora</p>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <a href="{{ route('validacao.formulario') }}" class="flex-1 bg-blue-900 hover:bg-blue-800 text-white px-6 py-3 rounded font-bold text-center transition-colors flex items-center justify-center">
                        <i class="fas fa-redo mr-2"></i>
                        TENTAR NOVAMENTE
                    </a>
                    <a href="mailto:{{ config('instituicao.contatos.email') }}?subject=Verificação de Documento - Código {{ $codigo }}" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded font-bold text-center transition-colors flex items-center justify-center">
                        <i class="fas fa-envelope mr-2"></i>
                        ENTRAR EM CONTATO
                    </a>
                </div>

                <!-- Informações de Contato -->
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wide text-center">
                        Canais Oficiais de Atendimento
                    </h4>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded border border-gray-200 text-center">
                            <i class="fas fa-phone text-blue-900 text-2xl mb-2"></i>
                            <p class="text-xs text-gray-600 mb-1">Telefone</p>
                            <p class="font-semibold text-gray-900 text-sm">{{ config('instituicao.contatos.telefone') }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded border border-gray-200 text-center">
                            <i class="fas fa-envelope text-blue-900 text-2xl mb-2"></i>
                            <p class="text-xs text-gray-600 mb-1">E-mail</p>
                            <p class="font-semibold text-gray-900 text-sm">{{ config('instituicao.contatos.email') }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded border border-gray-200 text-center">
                            <i class="fas fa-map-marker-alt text-blue-900 text-2xl mb-2"></i>
                            <p class="text-xs text-gray-600 mb-1">Endereço</p>
                            <p class="font-semibold text-gray-900 text-sm">{{ config('instituicao.endereco.rua') }}, Nº{{ config('instituicao.endereco.numero') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé Oficial -->
    <footer class="bg-gray-800 text-gray-300 mt-12 border-t-4 border-red-700">
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
</body>
</html>