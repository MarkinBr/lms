<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Autenticado - Sistema de Validação {{ config('instituicao.sigla') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Cabeçalho Oficial -->
    <div class="bg-white border-b-4 border-green-700">
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
                <span class="text-green-700 font-medium">Documento Autenticado</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Selo de Autenticidade -->
        <div class="text-center mb-6 no-print">
            <div class="inline-block bg-white rounded-full p-6 shadow-lg border-4 border-green-600">
                <i class="fas fa-certificate text-6xl text-green-600"></i>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-sm shadow-sm border border-gray-200">
            <!-- Cabeçalho de Sucesso -->
            <div class="bg-gradient-to-r from-green-700 to-green-600 px-8 py-6 border-b-2 border-green-800">
                <div class="flex items-center justify-center space-x-4">
                    <div class="bg-white rounded-full p-4">
                        <i class="fas fa-check-circle text-4xl text-green-600"></i>
                    </div>
                    <div class="text-white text-center">
                        <h2 class="text-3xl font-bold mb-1">DOCUMENTO AUTÊNTICO</h2>
                        <p class="text-green-100 text-sm">Validação Realizada com Sucesso</p>
                    </div>
                </div>
            </div>

            <!-- Conteúdo -->
            <div class="p-8">
                <!-- Informações de Validação -->
                <div class="bg-green-50 border-l-4 border-green-700 p-5 mb-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-2">Código de Validação</p>
                            <p class="text-2xl font-mono font-bold text-green-900 tracking-wider">{{ $declaracao->codigo_validacao }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-2">Data de Emissão</p>
                            <p class="text-2xl font-bold text-green-900">{{ $declaracao->data_emissao->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-green-200">
                        <p class="text-xs text-green-700">
                            <i class="fas fa-info-circle mr-1"></i>
                            Consulta realizada em {{ now()->format('d/m/Y') }} às {{ now()->format('H:i') }} | 
                            Protocolo: {{ strtoupper(substr(md5($declaracao->id . now()), 0, 8)) }}
                        </p>
                    </div>
                </div>

                <!-- Status de Validade -->
                @if($declaracao->isValido())
                    <div class="bg-green-50 border border-green-300 rounded p-5 mb-6">
                        <div class="flex items-center">
                            <div class="bg-green-600 rounded-full p-3 mr-4">
                                <i class="fas fa-shield-check text-white text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-bold text-green-900 text-sm mb-1">DOCUMENTO VÁLIDO</p>
                                <p class="text-sm text-green-800">Este documento está dentro do prazo de validade e possui autenticidade reconhecida pela instituição emissora.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-300 rounded p-5 mb-6">
                        <div class="flex items-center">
                            <div class="bg-yellow-500 rounded-full p-3 mr-4">
                                <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-bold text-yellow-900 text-sm mb-1">DOCUMENTO EXPIRADO</p>
                                <p class="text-sm text-yellow-800">Este documento foi emitido há mais de 6 meses. Recomenda-se solicitar uma nova via atualizada.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Dados da Matrícula -->
                <div class="border-t-2 border-gray-200 pt-6 mt-6">
                    <div class="bg-blue-900 text-white px-4 py-2 -mx-8 mb-6">
                        <h3 class="text-lg font-bold uppercase tracking-wide">
                            <i class="fas fa-graduation-cap mr-2"></i>
                            Informações Acadêmicas
                        </h3>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-8">
                        <!-- Coluna 1: Dados do Aluno -->
                        <div class="space-y-5">
                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">Nome Completo</p>
                                <p class="text-lg font-bold text-gray-900">{{ $matricula->user->name }}</p>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">CPF</p>
                                <p class="text-lg font-mono font-semibold text-gray-900">{{ $matricula->user->cpf }}</p>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">Número de Matrícula</p>
                                <p class="text-lg font-mono font-bold text-gray-900">{{ $matricula->numero_matricula }}</p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">Data da Matrícula</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $matricula->data_matricula->format('d/m/Y') }}</p>
                            </div>
                        </div>

                        <!-- Coluna 2: Dados Acadêmicos -->
                        <div class="space-y-5">
                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">Curso</p>
                                <p class="text-lg font-bold text-gray-900">{{ $matricula->curso->titulo }}</p>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">Turma</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $matricula->turma->nome ?? 'Não Informado' }}</p>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">Ano Letivo</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $matricula->turma->ano_letivo ?? 'Não Informado' }}</p>
                            </div>

                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide mb-2">Situação Acadêmica</p>
                                @if($matricula->status === 'ativo')
                                    <span class="inline-flex items-center px-4 py-2 rounded font-bold text-sm bg-green-600 text-white">
                                        <i class="fas fa-check-circle mr-2"></i> ATIVO - REGULAR
                                    </span>
                                @elseif($matricula->status === 'trancado')
                                    <span class="inline-flex items-center px-4 py-2 rounded font-bold text-sm bg-yellow-600 text-white">
                                        <i class="fas fa-pause-circle mr-2"></i> TRANCADO
                                    </span>
                                @elseif($matricula->status === 'concluido')
                                    <span class="inline-flex items-center px-4 py-2 rounded font-bold text-sm bg-blue-600 text-white">
                                        <i class="fas fa-graduation-cap mr-2"></i> CONCLUÍDO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-4 py-2 rounded font-bold text-sm bg-gray-600 text-white">
                                        {{ strtoupper($matricula->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas de Acesso -->
                <div class="border-t border-gray-200 pt-6 mt-8">
                    <div class="bg-gray-50 rounded border border-gray-200 p-6">
                        <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wide">Registro de Acessos</h4>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="bg-white rounded border border-gray-300 p-4">
                                    <i class="fas fa-eye text-blue-900 text-2xl mb-2"></i>
                                    <p class="text-3xl font-bold text-gray-900">{{ $declaracao->quantidade_visualizacoes }}</p>
                                    <p class="text-xs text-gray-600 mt-1">Visualizações</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="bg-white rounded border border-gray-300 p-4">
                                    <i class="fas fa-calendar text-blue-900 text-2xl mb-2"></i>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ $declaracao->data_emissao->format('d/m/Y') }}
                                    </p>
                                    <p class="text-xs text-gray-600 mt-1">Primeira Emissão</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="bg-white rounded border border-gray-300 p-4">
                                    <i class="fas fa-clock text-blue-900 text-2xl mb-2"></i>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ $declaracao->ultima_visualizacao?->format('d/m/Y H:i') ?? 'Agora' }}
                                    </p>
                                    <p class="text-xs text-gray-600 mt-1">Última Verificação</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações de Segurança -->
                <div class="bg-blue-50 border-l-4 border-blue-900 p-5 mt-6">
                    <div class="flex items-start">
                        <i class="fas fa-lock text-blue-900 text-xl mt-1 mr-4"></i>
                        <div>
                            <p class="font-bold text-blue-900 mb-2 text-sm">AUTENTICIDADE VERIFICADA</p>
                            <p class="text-sm text-blue-800 leading-relaxed">
                                Este documento foi emitido oficialmente pelo {{ config('instituicao.sigla') }} e possui validade jurídica. A autenticidade foi confirmada através de nosso sistema de validação digital com criptografia de dados. Qualquer alteração nas informações aqui apresentadas invalida automaticamente este documento.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Aviso de Conformidade -->
                <div class="bg-gray-50 border border-gray-300 rounded p-5 mt-6">
                    <div class="flex items-start">
                        <i class="fas fa-gavel text-gray-700 text-xl mt-1 mr-4"></i>
                        <div>
                            <p class="font-bold text-gray-900 mb-2 text-sm">DECLARAÇÃO DE CONFORMIDADE</p>
                            <p class="text-xs text-gray-700 leading-relaxed">
                                Este documento é emitido pelo {{ config('instituicao.nome_extenso') }} e possui validade legal. 
                                A falsificação ou uso indevido constitui crime previsto nos artigos 297 e 304 do Código Penal Brasileiro.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-col sm:flex-row gap-3 mt-8 no-print">
                    <button onclick="window.print()" class="flex-1 bg-blue-900 hover:bg-blue-800 text-white px-6 py-4 rounded font-bold text-center transition-colors flex items-center justify-center">
                        <i class="fas fa-print text-xl mr-3"></i>
                        <span>IMPRIMIR COMPROVANTE</span>
                    </button>
                    <a href="{{ route('validacao.formulario') }}" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white px-6 py-4 rounded font-bold text-center transition-colors flex items-center justify-center">
                        <i class="fas fa-search text-xl mr-3"></i>
                        <span>VALIDAR OUTRO DOCUMENTO</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="mt-6 bg-white rounded-sm shadow-sm border border-gray-200 p-6">
            <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wide text-center">
                Orientações para Verificação
            </h4>
            <div class="grid md:grid-cols-3 gap-4 text-center text-xs text-gray-700">
                <div>
                    <i class="fas fa-file-contract text-blue-900 text-xl mb-2"></i>
                    <p class="font-semibold mb-1">Compare os Dados</p>
                    <p>Verifique se as informações apresentadas correspondem ao documento físico</p>
                </div>
                <div>
                    <i class="fas fa-shield-check text-blue-900 text-xl mb-2"></i>
                    <p class="font-semibold mb-1">Validação Digital</p>
                    <p>Certifique-se de que o código QR está presente no documento original</p>
                </div>
                <div>
                    <i class="fas fa-phone-alt text-blue-900 text-xl mb-2"></i>
                    <p class="font-semibold mb-1">Canais Oficiais</p>
                    <p>Em caso de dúvidas, entre em contato através dos nossos canais oficiais</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé Oficial -->
    <footer class="bg-gray-800 text-gray-300 mt-12 border-t-4 border-green-700">
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

    <!-- Print Styles -->
    <style>
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .container {
                max-width: 100%;
            }
        }
    </style>
</body>
</html>