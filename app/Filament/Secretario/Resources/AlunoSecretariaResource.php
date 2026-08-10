<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\AlunoSecretariaResource\Pages;
use App\Models\User;
use App\Models\Curso;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Filament\Infolists\Infolist;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewer;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Components\ViewEntry;



class AlunoSecretariaResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Aluno';
    protected static ?string $pluralModelLabel = 'Alunos';
    protected static ?string $navigationLabel = 'Alunos';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = '📚 Acadêmico';
    protected static ?int $navigationSort = 2;
    protected static function gerarCaminhoDocumento($record, $tipo, $arquivoOriginal): string
{
    // Remove caracteres especiais do CPF
    $cpfLimpo = preg_replace('/[^0-9]/', '', $record->cpf ?? 'SEM_CPF');
    
    // Remove acentos e caracteres especiais do nome
    $nomeLimpo = strtoupper(self::removerAcentos($record->name ?? 'ALUNO'));
    $nomeLimpo = preg_replace('/[^A-Z0-9\s]/', '', $nomeLimpo);
    $nomeLimpo = preg_replace('/\s+/', '_', trim($nomeLimpo));
    
    // Limita o tamanho do nome para não ficar muito longo
    $nomeLimpo = substr($nomeLimpo, 0, 30);
    
    // Pega a primeira matrícula ativa (se houver)
    $matricula = $record->matriculas()
        ->where('status', 'ativo')
        ->first();
    $numeroMatricula = $matricula ? str_pad($matricula->id, 6, '0', STR_PAD_LEFT) : 'SEM_MAT';
    
    // Nome da pasta do aluno
    $pastaAluno = "alunos/{$cpfLimpo}_{$nomeLimpo}";
    
    // Mapeia os tipos de documento para nomes curtos
    $tiposMap = [
        'RG' => 'RG',
        'CPF' => 'CPF',
        'COMP_RESIDENCIA' => 'COMP_RESIDENCIA',
        'CERT_NASC_CAS' => 'CERTIDAO',
        'HISTORICO' => 'HISTORICO',
        'DIPLOMA' => 'DIPLOMA',
        'FOTO_3X4' => 'FOTO_3X4',
        'NADA_CONSTA' => 'NADA_CONSTA',
        'OUTROS' => 'OUTROS',
    ];
    
    $tipoLimpo = $tiposMap[$tipo] ?? 'DOCUMENTO';
    
    // Data atual
    $dataAtual = date('Ymd_His');
    
    // Extensão do arquivo original
    $extensao = pathinfo($arquivoOriginal, PATHINFO_EXTENSION);
    
    // Monta o nome final
    // Correcao: caminho duplicado no upload de documentos
    return "{$tipoLimpo}_{$cpfLimpo}_MAT{$numeroMatricula}_{$dataAtual}.{$extensao}";

    
    return "{$pastaAluno}/{$nomeArquivo}";
}

/**
 * Remove acentos de uma string
 */
protected static function removerAcentos($string): string
{
    $map = [
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
        'Ç'=>'C',
        'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
        'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
        'Ñ'=>'N',
        'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O',
        'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
        'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
        'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
        'ñ'=>'n',
        'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
        'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u',
    ];
    
    return strtr($string, $map);
}


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('nivel', 'aluno');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SEÇÃO 1: DADOS PESSOAIS ---
                Forms\Components\Section::make('Dados Pessoais')
                    ->description('Informações básicas do aluno')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        // 📸 FOTO COM CAPTURA DE CÂMERA
                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto do Aluno')
                            ->avatar()
                            ->disk('public')       
                            ->visibility('public') 
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('alunos')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/*'])
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('400')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('center')
                            ->uploadButtonPosition('center')
                            ->uploadProgressIndicatorPosition('center')
                            ->columnSpanFull()
                            ->alignCenter()
                            ->helperText('📱 Use a câmera do celular ou selecione uma foto'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nome Completo')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-user'),

                            Forms\Components\TextInput::make('email')
                                ->label('E-mail')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-o-envelope'),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('telefone')
                                ->label('Telefone/WhatsApp')
                                ->mask('(99) 99999-9999')
                                ->required()
                                ->prefixIcon('heroicon-o-phone'),

                            Forms\Components\TextInput::make('cpf')
                                ->label('CPF')
                                ->mask('999.999.999-99')
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->prefixIcon('heroicon-o-identification'),

                            Forms\Components\DatePicker::make('data_nasc')
                                ->label('Data de Nascimento')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->prefixIcon('heroicon-o-cake'),
                        ]),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Aluno Ativo')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Hidden::make('nivel')
                            ->default('aluno'),
                        
                        Forms\Components\Hidden::make('password')
                            ->default(fn() => bcrypt('aluno@123'))
                            ->visibleOn('create'), 
                    ])
                    ->collapsible(),

                // --- SEÇÃO 2: ENDEREÇO ---
                Forms\Components\Section::make('Endereço')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make('cep')
                                ->label('CEP')
                                ->mask('99999-999')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (!$state) return;
                                    $cep = preg_replace('/[^0-9]/', '', $state);
                                    if (strlen($cep) !== 8) return;

                                    $response = Http::get("https://viacep.com.br/ws/$cep/json/")->json();
                                    if (!isset($response['erro'])) {
                                        $set('endereco', $response['logradouro'] ?? '');
                                        $set('bairro', $response['bairro'] ?? '');
                                        $set('cidade', $response['localidade'] ?? '');
                                        $set('estado', $response['uf'] ?? '');
                                    }
                                })->columnSpan(2),

                            Forms\Components\TextInput::make('numero')
                                ->label('Número')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('complemento')
                                ->label('Complemento')
                                ->columnSpan(1),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('endereco')
                                ->label('Logradouro')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('bairro')
                                ->label('Bairro'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('cidade')
                                ->label('Cidade'),

                            Forms\Components\Select::make('estado')
                                ->label('Estado (UF)')
                                ->options([
                                    'AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'Rondônia','RR'=>'Roraima','SC'=>'Santa Catarina','SP'=>'São Paulo','SE'=>'Sergipe','TO'=>'Tocantins'
                                ])
                                ->searchable()
                                ->native(false),
                        ]),
                    ])->collapsed(),

                // --- SEÇÃO 3: RESPONSÁVEL ---
                Forms\Components\Section::make('Dados do Responsável')
                    ->description('Informações do pai/mãe ou responsável legal')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('responsavel_nome')
                                ->label('Nome do Responsável')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('responsavel_telefone')
                                ->label('Telefone do Responsável')
                                ->mask('(99) 99999-9999'),
                        ]),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsed(),

                // --- SEÇÃO 3.5: DOCUMENTOS 📄 ---
                Forms\Components\Section::make('📋 Documentos do Aluno')
    ->description('Gerencie os documentos necessários para a matrícula')
    ->icon('heroicon-o-document-text')
    ->schema([
        Forms\Components\Repeater::make('documentos')
            ->label('')
            ->columnSpanFull()
            ->schema([
                Forms\Components\Grid::make(12)->schema([
                    // COLUNA 1: Tipo de Documento (3 colunas)
                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de Documento')
                        ->options([
                            'RG' => '🪪 RG (Identidade)',
                            'CPF' => '🔢 CPF',
                            'COMP_RESIDENCIA' => '🏠 Comp. Residência',
                            'CERT_NASC_CAS' => '💍 Cert. Nascimento/Casamento',
                            'HISTORICO' => '🎓 Histórico Escolar',
                            'DIPLOMA' => '📜 Diploma/Certificado',
                            'FOTO_3X4' => '📸 Foto 3x4',
                            'NADA_CONSTA' => '⚖️ Nada Consta',
                            'OUTROS' => '📂 Outros',
                        ])
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->columnSpan(3),

                    // COLUNA 2: Data de Emissão (2 colunas)
                    Forms\Components\DatePicker::make('data_emissao')
                        ->label('Data de Emissão')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->columnSpan(2),

                    // COLUNA 3: Upload do Arquivo (4 colunas) - COM ORGANIZAÇÃO AUTOMÁTICA
                    Forms\Components\FileUpload::make('arquivo')
                        ->label('Arquivo')
                        ->disk('public')
                        ->visibility('public')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->maxSize(5120)
                        ->imagePreviewHeight('80')
                        ->downloadable()
                        ->openable()
                        ->previewable(true)
                        ->live()
                        ->columnSpan(4)
                        ->helperText('PDF ou Imagem (máx. 5MB)')
                        // 🎯 AQUI É A MÁGICA: Renomeia e organiza automaticamente
                        ->getUploadedFileNameForStorageUsing(function ($file, $get, $record) {
                            $tipo = $get('tipo');
                            if (!$tipo || !$record) {
                                // Se não tiver tipo ou record, usa nome padrão
                                return 'alunos/temp/' . uniqid() . '.' . $file->getClientOriginalExtension();
                            }
                            
                            $nomeOriginal = $file->getClientOriginalName();
                            return self::gerarCaminhoDocumento($record, $tipo, $nomeOriginal);
                        })
                        ->directory(function ($record) {
                            if (!$record) return 'alunos/temp';
                            
                            $cpfLimpo = preg_replace('/[^0-9]/', '', $record->cpf ?? 'SEM_CPF');
                            $nomeLimpo = strtoupper(self::removerAcentos($record->name ?? 'ALUNO'));
                            $nomeLimpo = preg_replace('/[^A-Z0-9\s]/', '', $nomeLimpo);
                            $nomeLimpo = preg_replace('/\s+/', '_', trim($nomeLimpo));
                            $nomeLimpo = substr($nomeLimpo, 0, 30);
                            
                            return "alunos/{$cpfLimpo}_{$nomeLimpo}";
                        }),

                    // COLUNA 4: Status e Ações (3 colunas)
                    Forms\Components\Group::make([
                        Forms\Components\Placeholder::make('status_visualizacao')
                            ->label('Status')
                            ->content(function ($get) {
                                $arquivo = $get('arquivo');
                                
                                if (is_array($arquivo)) {
                                    $arquivo = array_values($arquivo)[0] ?? null;
                                }

                                if (!$arquivo) {
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-3 w-3 relative">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                                            </span>
                                            <span class="text-sm font-medium text-yellow-600">Pendente</span>
                                        </div>
                                    ');
                                }

                                $url = asset('storage/' . $arquivo);
                                $isPdf = str_ends_with(strtolower($arquivo), '.pdf');
                                
                                $viewUrl = $isPdf 
                                    ? $url . '#toolbar=0&navpanes=0&scrollbar=0' 
                                    : $url;

                                $icon = $isPdf ? '📄' : '🖼️';
                                $cor = $isPdf ? 'red' : 'blue';
                                
                                // Mostra o nome do arquivo de forma amigável
                                $nomeArquivo = basename($arquivo);
                                $nomeExibicao = strlen($nomeArquivo) > 25 
                                    ? substr($nomeArquivo, 0, 22) . '...' 
                                    : $nomeArquivo;

                                return new \Illuminate\Support\HtmlString("
                                    <div class='flex flex-col gap-2'>
                                        <div class='flex items-center gap-2 mb-1'>
                                            <span class='flex h-3 w-3 relative'>
                                                <span class='relative inline-flex rounded-full h-3 w-3 bg-green-500'></span>
                                            </span>
                                            <span class='text-sm font-medium text-green-600'>Enviado</span>
                                        </div>
                                        
                                        <div class='text-xs text-gray-500 mb-1 truncate' title='{$nomeArquivo}'>
                                            📎 {$nomeExibicao}
                                        </div>
                                        
                                        <div class='flex gap-1'>
                                            <a href='{$viewUrl}' 
                                               target='_blank'
                                               onclick=\"window.open(this.href, 'DocumentViewer', 'width=900,height=700,menubar=no,toolbar=no,location=no'); return false;\"
                                               class='flex items-center justify-center gap-1 px-3 py-1.5 rounded-md text-xs font-semibold border transition-all hover:shadow-md text-{$cor}-700 bg-{$cor}-50 hover:bg-{$cor}-100 border-{$cor}-200'>
                                               {$icon} Ver
                                            </a>
                                            
                                            <a href='{$url}' 
                                               download
                                               class='flex items-center justify-center gap-1 px-3 py-1.5 rounded-md text-xs font-semibold border transition-all hover:shadow-md text-gray-700 bg-gray-50 hover:bg-gray-100 border-gray-200'>
                                               ⬇️
                                            </a>
                                        </div>
                                    </div>
                                ");
                            }),
                    ])->columnSpan(3),
                ]),

                // Observações sobre o documento (linha separada, ocupando tudo)
                Forms\Components\Textarea::make('observacoes')
                    ->label('Observações')
                    ->rows(2)
                    ->placeholder('Ex: Documento vencido, precisa renovar...')
                    ->columnSpanFull(),
            ])
            ->itemLabel(function (array $state): ?string {
                if (empty($state['tipo'])) return 'Novo Documento';
                
                $tipos = [
                    'RG' => '🪪 RG',
                    'CPF' => '🔢 CPF',
                    'COMP_RESIDENCIA' => '🏠 Comp. Residência',
                    'CERT_NASC_CAS' => '💍 Certidão',
                    'HISTORICO' => '🎓 Histórico',
                    'DIPLOMA' => '📜 Diploma',
                    'FOTO_3X4' => '📸 Foto 3x4',
                    'NADA_CONSTA' => '⚖️ Nada Consta',
                    'OUTROS' => '📂 Outros',
                ];
                
                $label = $tipos[$state['tipo']] ?? $state['tipo'];
                
                // Adiciona indicador de status
                $hasFile = !empty($state['arquivo']);
                $statusIcon = $hasFile ? '✅' : '⏳';
                
                return $statusIcon . ' ' . $label;
            })
            ->collapsible()
            ->collapsed(fn ($state) => !empty($state['arquivo'])) // Colapsa se já tiver arquivo
            ->cloneable()
            ->collapseAllAction(fn ($action) => $action->label('Recolher Todos'))
            ->expandAllAction(fn ($action) => $action->label('Expandir Todos'))
            ->reorderableWithButtons()
            ->addActionLabel('➕ Adicionar Documento')
            ->defaultItems(0)
            ->grid(1)

            ->extraItemActions([
    Forms\Components\Actions\Action::make('visualizar')
        ->label('Ver')
        ->icon('heroicon-m-eye')
        ->color('info')
        ->modalHeading('Visualização do Documento')
        ->modalContent(function (array $arguments, $record, $component) {

            // Pega o estado atual do repeater
            $dadosItem = $component->getState();
            $arquivo = $dadosItem[$arguments['item']]['arquivo'] ?? null;

            // Segurança extra se vier array
            if (is_array($arquivo)) {
                $arquivo = array_values($arquivo)[0] ?? null;
            }

            if (!$arquivo) {
                return new HtmlString(
                    '<p class="text-center p-4">⚠️ Nenhum arquivo carregado.</p>'
                );
            }

            // URL pública do arquivo
            $url = asset('storage/' . $arquivo);

            // 👉 PDF: usa PdfViewer via Infolist
            if (str_ends_with(strtolower($arquivo), '.pdf')) {
    return view('filament.pdf-viewer', [
        'url' => $url,
    ]);
}


            // 👉 Imagem: renderiza normal
            return new HtmlString("
                <div class='flex justify-center'>
                    <img
                        src='{$url}'
                        style='max-height: 80vh; max-width: 100%; border-radius: 8px;'
                    >
                </div>
            ");
        })
        ->modalSubmitAction(false)
        ->modalCancelAction(fn ($action) => $action->label('Fechar'))
        ->modalWidth('5xl'),
    ]),


        // RESUMO DE DOCUMENTOS PENDENTES
        Forms\Components\Placeholder::make('resumo_documentos')
            ->label('')
            ->content(function ($get) {
                $documentos = $get('documentos') ?? [];
                
                if (empty($documentos)) {
                    return new \Illuminate\Support\HtmlString('
                        <div class="rounded-lg border-2 border-dashed border-gray-300 p-4 text-center">
                            <div class="text-gray-400 text-sm">
                                📎 Nenhum documento adicionado ainda
                            </div>
                            <div class="text-xs text-gray-400 mt-2">
                                💡 Os arquivos serão organizados automaticamente na pasta do aluno
                            </div>
                        </div>
                    ');
                }

                $total = count($documentos);
                $enviados = collect($documentos)->filter(function ($doc) {
                    $arquivo = $doc['arquivo'] ?? null;
                    if (is_array($arquivo)) {
                        $arquivo = array_values($arquivo)[0] ?? null;
                    }
                    return !empty($arquivo);
                })->count();
                
                $pendentes = $total - $enviados;
                
                $progressoPercent = $total > 0 ? round(($enviados / $total) * 100) : 0;
                
                $corBarra = $progressoPercent == 100 ? 'bg-green-500' : 'bg-yellow-500';
                $corTexto = $progressoPercent == 100 ? 'text-green-700' : 'text-yellow-700';
                $corFundo = $progressoPercent == 100 ? 'bg-green-50' : 'bg-yellow-50';
                $corBorda = $progressoPercent == 100 ? 'border-green-200' : 'border-yellow-200';

                return new \Illuminate\Support\HtmlString("
                    <div class='rounded-lg border-2 {$corBorda} {$corFundo} p-4'>
                        <div class='flex items-center justify-between mb-3'>
                            <div class='flex items-center gap-2'>
                                <span class='text-2xl'>" . ($progressoPercent == 100 ? '✅' : '📋') . "</span>
                                <div>
                                    <div class='font-bold {$corTexto}'>
                                        Progresso: {$enviados}/{$total} documentos
                                    </div>
                                    <div class='text-sm text-gray-600'>
                                        " . ($pendentes > 0 ? "⏳ {$pendentes} pendente(s)" : "🎉 Todos os documentos enviados!") . "
                                    </div>
                                </div>
                            </div>
                            <div class='text-3xl font-bold {$corTexto}'>
                                {$progressoPercent}%
                            </div>
                        </div>
                        
                        <div class='w-full bg-gray-200 rounded-full h-3 overflow-hidden'>
                            <div class='{$corBarra} h-3 rounded-full transition-all duration-500 shadow-sm' 
                                 style='width: {$progressoPercent}%'>
                            </div>
                        </div>
                        
                        <div class='mt-3 text-xs text-gray-500 flex items-center gap-1'>
                            <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'></path>
                            </svg>
                            Arquivos organizados automaticamente por CPF + Nome
                        </div>
                    </div>
                ");
            })
            ->columnSpanFull(),
    ])
    ->collapsed()
    ->collapsible(),

                // --- SEÇÃO 4: CRIAR MATRÍCULA (APENAS NA CRIAÇÃO) ---
                Forms\Components\Section::make('🎓 Criar Matrícula Junto? (Opcional)')
                    ->description('Marque para já criar a matrícula do aluno')
                    ->icon('heroicon-o-document-plus')
                    ->hiddenOn('edit')
                    ->schema([
                        Forms\Components\Toggle::make('criar_matricula')
                            ->label('Sim, criar matrícula agora')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('curso_id_matricula')
                                    ->label('Curso')
                                    ->options(Curso::where('ativo', true)->pluck('titulo', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('turma_id_matricula', null))
                                    ->required(fn (Get $get) => $get('criar_matricula'))
                                    ->visible(fn (Get $get) => $get('criar_matricula')),

                                Forms\Components\Select::make('turma_id_matricula')
                                    ->label('Turma')
                                    ->options(function (Get $get) {
                                        $cursoId = $get('curso_id_matricula');
                                        if (!$cursoId) return [];
                                        return Turma::where('ativa', true)
                                            ->where('curso_id', $cursoId)
                                            ->pluck('nome', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->disabled(fn (Get $get) => !$get('curso_id_matricula'))
                                    ->required(fn (Get $get) => $get('criar_matricula'))
                                    ->visible(fn (Get $get) => $get('criar_matricula')),

                                Forms\Components\DatePicker::make('data_matricula_nova')
                                    ->label('Data da Matrícula')
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->required(fn (Get $get) => $get('criar_matricula'))
                                    ->visible(fn (Get $get) => $get('criar_matricula')),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('dia_vencimento_nova')
                                    ->label('Dia do Vencimento')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->default(10)
                                    ->suffix('do mês')
                                    ->required(fn (Get $get) => $get('criar_matricula'))
                                    ->visible(fn (Get $get) => $get('criar_matricula')),

                                Forms\Components\Toggle::make('gerar_mensalidades')
                                    ->label('Gerar Mensalidades Automaticamente?')
                                    ->default(false)
                                    ->live()
                                    ->visible(fn (Get $get) => $get('criar_matricula')),
                            ]),

                        // DADOS DAS MENSALIDADES
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('valor_mensalidade_nova')
                                    ->label('Valor da Mensalidade')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->step(0.01)
                                    ->required(fn (Get $get) => $get('gerar_mensalidades'))
                                    ->visible(fn (Get $get) => $get('gerar_mensalidades')),

                                Forms\Components\TextInput::make('qtd_parcelas_nova')
                                    ->label('Quantidade de Parcelas')
                                    ->numeric()
                                    ->default(12)
                                    ->minValue(1)
                                    ->maxValue(48)
                                    ->suffix('parcelas')
                                    ->required(fn (Get $get) => $get('gerar_mensalidades'))
                                    ->visible(fn (Get $get) => $get('gerar_mensalidades')),

                                Forms\Components\DatePicker::make('primeira_mensalidade_nova')
                                    ->label('Vencimento 1ª Mensalidade')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now()->addMonth()->day(10))
                                    ->required(fn (Get $get) => $get('gerar_mensalidades'))
                                    ->visible(fn (Get $get) => $get('gerar_mensalidades')),
                            ]),
                    ])
                    ->collapsed()
                    ->collapsible(),

                // --- SEÇÃO 5: CURSOS E MATRÍCULAS (APENAS NA EDIÇÃO) ---
                Forms\Components\Section::make('Cursos & Matrículas')
                    ->description('Lista de cursos em que este aluno está matriculado')
                    ->icon('heroicon-o-academic-cap')
                    ->hiddenOn('create')
                    ->schema([
                        Forms\Components\Repeater::make('matriculas')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(5)->schema([
                                    Forms\Components\Select::make('curso_id')
                                        ->label('Curso')
                                        ->relationship('curso', 'titulo')
                                        ->disabled()
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('turma_id')
                                        ->label('Turma')
                                        ->relationship('turma', 'nome')
                                        ->disabled()
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('status')
                                        ->label('Situação')
                                        ->options([
                                            'ativo' => '✅ Ativo',
                                            'trancado' => '⏸️ Trancado',
                                            'concluido' => '🎓 Concluído',
                                            'cancelado' => '❌ Cancelado',
                                        ])
                                        ->disabled()
                                        ->columnSpan(1),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('editar_matricula')
                                            ->label('Editar')
                                            ->icon('heroicon-m-pencil-square')
                                            ->color('primary')
                                            ->url(fn ($record) => route('filament.secretario.resources.matricula-secretarias.edit', $record))
                                            ->tooltip('Ir para página da matrícula'),
                                    ])
                                    ->alignEnd()
                                    ->columnSpan(1),
                                ]),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email),

                Tables\Columns\TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('telefone')
                    ->label('Contato')
                    ->searchable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('matriculas_count')
                    ->label('Matrículas')
                    ->counts('matriculas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos'),

                Tables\Filters\Filter::make('com_matriculas')
                    ->label('Com Matrículas Ativas')
                    ->query(fn (Builder $query) => 
                        $query->whereHas('matriculas', fn ($q) => 
                            $q->where('status', 'ativo')
                        )
                    ),

                Tables\Filters\Filter::make('sem_matriculas')
                    ->label('Sem Matrículas')
                    ->query(fn (Builder $query) => 
                        $query->doesntHave('matriculas')
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('nova_matricula')
                        ->label('Nova Matrícula')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->url(fn ($record) => route('filament.secretario.resources.matricula-secretarias.create', [
                            'user_id' => $record->id
                        ])),

                    // Dentro de ->actions([ Tables\Actions\ActionGroup::make([ ... aqui ... ]) ])

Tables\Actions\Action::make('ver_documentos')
    ->label('Documentos')
    ->icon('heroicon-o-document-text')
    ->color('info')
    ->badge(fn ($record) => collect($record->documentos ?? [])->filter(function ($doc) {
        $arquivo = $doc['arquivo'] ?? null;
        if (is_array($arquivo)) $arquivo = array_values($arquivo)[0] ?? null;
        return !empty($arquivo);
    })->count())
    ->modalHeading(fn ($record) => '📋 Documentos de ' . $record->name)
    ->modalDescription(fn ($record) => 'Total: ' . count($record->documentos ?? []) . ' documento(s) cadastrado(s)')
    ->modalWidth('5xl')
    ->modalSubmitAction(false)
    ->modalCancelActionLabel('Fechar')
    ->modalContent(function ($record) {
        $documentos = $record->documentos ?? [];
        
        if (empty($documentos)) {
            return new \Illuminate\Support\HtmlString('
                <div class="text-center py-12 text-gray-400">
                    <div class="text-6xl mb-4">📎</div>
                    <div class="text-lg font-semibold">Nenhum documento cadastrado</div>
                </div>
            ');
        }

        $tipos = [
            'RG' => ['emoji' => '🪪', 'nome' => 'RG (Identidade)', 'cor' => 'blue'],
            'CPF' => ['emoji' => '🔢', 'nome' => 'CPF', 'cor' => 'indigo'],
            'COMP_RESIDENCIA' => ['emoji' => '🏠', 'nome' => 'Comprovante de Residência', 'cor' => 'green'],
            'CERT_NASC_CAS' => ['emoji' => '💍', 'nome' => 'Certidão', 'cor' => 'pink'],
            'HISTORICO' => ['emoji' => '🎓', 'nome' => 'Histórico Escolar', 'cor' => 'purple'],
            'DIPLOMA' => ['emoji' => '📜', 'nome' => 'Diploma/Certificado', 'cor' => 'yellow'],
            'FOTO_3X4' => ['emoji' => '📸', 'nome' => 'Foto 3x4', 'cor' => 'cyan'],
            'NADA_CONSTA' => ['emoji' => '⚖️', 'nome' => 'Nada Consta', 'cor' => 'gray'],
            'OUTROS' => ['emoji' => '📂', 'nome' => 'Outros', 'cor' => 'slate'],
        ];

        $enviados = 0;
        $pendentes = 0;

        $html = '<div class="space-y-3">';
        
        foreach ($documentos as $doc) {
            $tipo = $doc['tipo'] ?? 'OUTROS';
            $info = $tipos[$tipo] ?? ['emoji' => '📄', 'nome' => $tipo, 'cor' => 'gray'];
            $arquivo = $doc['arquivo'] ?? null;
            
            if (is_array($arquivo)) {
                $arquivo = array_values($arquivo)[0] ?? null;
            }

            $hasFile = !empty($arquivo);
            $hasFile ? $enviados++ : $pendentes++;

            $data = !empty($doc['data_emissao']) ? date('d/m/Y', strtotime($doc['data_emissao'])) : 'Sem data';
            $obs = !empty($doc['observacoes']) ? $doc['observacoes'] : '';

            if ($hasFile) {
                $url = asset('storage/' . $arquivo);
                $isPdf = str_ends_with(strtolower($arquivo), '.pdf');
                $viewUrl = $isPdf ? $url . '#toolbar=0&navpanes=0&scrollbar=0' : $url;
                
                $html .= "
                <div class='flex items-start gap-3 p-4 rounded-lg border-2 border-{$info['cor']}-200 bg-{$info['cor']}-50 hover:shadow-md transition-all'>
                    <div class='text-4xl'>{$info['emoji']}</div>
                    <div class='flex-1'>
                        <div class='flex items-center gap-2 mb-1'>
                            <span class='font-bold text-{$info['cor']}-700'>{$info['nome']}</span>
                            <span class='px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700'>
                                ✓ Enviado
                            </span>
                        </div>
                        <div class='text-sm text-gray-600 mb-2'>
                            📅 Emissão: {$data}
                        </div>
                        " . (!empty($obs) ? "<div class='text-sm text-gray-500 italic mb-2'>💬 {$obs}</div>" : "") . "
                        <div class='flex gap-2'>
                            <a href='{$viewUrl}' 
                               target='_blank'
                               onclick=\"window.open(this.href, 'DocViewer', 'width=1000,height=800,menubar=no,toolbar=no,location=no'); return false;\"
                               class='inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-semibold bg-white border-2 border-{$info['cor']}-300 text-{$info['cor']}-700 hover:bg-{$info['cor']}-100 transition-all hover:shadow-sm'>
                                " . ($isPdf ? '📄 Visualizar PDF' : '🖼️ Ver Imagem') . "
                            </a>
                            <a href='{$url}' 
                               download
                               class='inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-semibold bg-white border-2 border-gray-300 text-gray-700 hover:bg-gray-100 transition-all hover:shadow-sm'>
                                ⬇️ Baixar
                            </a>
                        </div>
                    </div>
                </div>
                ";
            } else {
                $html .= "
                <div class='flex items-start gap-3 p-4 rounded-lg border-2 border-dashed border-yellow-300 bg-yellow-50'>
                    <div class='text-4xl opacity-50'>{$info['emoji']}</div>
                    <div class='flex-1'>
                        <div class='flex items-center gap-2 mb-1'>
                            <span class='font-bold text-gray-700'>{$info['nome']}</span>
                            <span class='px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700'>
                                ⏳ Pendente
                            </span>
                        </div>
                        <div class='text-sm text-gray-500'>
                            Documento ainda não foi enviado
                        </div>
                    </div>
                </div>
                ";
            }
        }

        $total = count($documentos);
        $progresso = $total > 0 ? round(($enviados / $total) * 100) : 0;

        $html .= "
        </div>
        
        <div class='mt-6 p-4 rounded-lg bg-gray-50 border border-gray-200'>
            <div class='flex items-center justify-between mb-2'>
                <span class='font-bold text-gray-700'>📊 Resumo</span>
                <span class='text-2xl font-bold " . ($progresso == 100 ? 'text-green-600' : 'text-yellow-600') . "'>{$progresso}%</span>
            </div>
            <div class='grid grid-cols-3 gap-4 text-center mb-3'>
                <div>
                    <div class='text-2xl font-bold text-gray-700'>{$total}</div>
                    <div class='text-xs text-gray-500'>Total</div>
                </div>
                <div>
                    <div class='text-2xl font-bold text-green-600'>{$enviados}</div>
                    <div class='text-xs text-gray-500'>Enviados</div>
                </div>
                <div>
                    <div class='text-2xl font-bold text-yellow-600'>{$pendentes}</div>
                    <div class='text-xs text-gray-500'>Pendentes</div>
                </div>
            </div>
            <div class='w-full bg-gray-200 rounded-full h-2 overflow-hidden'>
                <div class='" . ($progresso == 100 ? 'bg-green-500' : 'bg-yellow-500') . " h-2 rounded-full transition-all duration-500' style='width: {$progresso}%'></div>
            </div>
        </div>
        ";

        return new \Illuminate\Support\HtmlString($html);
    }),

                    Tables\Actions\Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(fn (User $record) => 
                            "https://api.whatsapp.com/send?phone=55" . 
                            preg_replace('/[^0-9]/', '', $record->telefone)
                        )
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Ações')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('ativar')
                        ->label('Ativar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['ativo' => true])),

                    Tables\Actions\BulkAction::make('desativar')
                        ->label('Desativar Selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['ativo' => false])),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhum aluno cadastrado')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlunosSecretaria::route('/'),
            'create' => Pages\CreateAlunoSecretaria::route('/create'),
            'edit' => Pages\EditAlunoSecretaria::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('nivel', 'aluno')->where('ativo', true)->count();
    }
}