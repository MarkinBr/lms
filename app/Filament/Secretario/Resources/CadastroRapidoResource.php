<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\CadastroRapidoResource\Pages;
use App\Models\User;
use App\Models\Curso;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class CadastroRapidoResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Cadastro Rápido';
    protected static ?string $pluralModelLabel = 'Cadastro Rápido';
    protected static ?string $navigationLabel = '⚡ Cadastro Rápido';
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationGroup = '📚 Acadêmico';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // ===== ETAPA 1 =====
                    Forms\Components\Wizard\Step::make('👤 Dados Pessoais')
                        ->description('Informações básicas do aluno')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\FileUpload::make('foto')
                                        ->label('Foto do Aluno')
                                        ->avatar()
                                        ->disk('public')
                                        ->visibility('public')
                                        ->imageEditor()
                                        ->circleCropper()
                                        ->directory('alunos')
                                        ->maxSize(15360)
                                        ->acceptedFileTypes(['image/*'])
                                        ->image()
                                        ->imageResizeMode('cover')
                                        ->imageCropAspectRatio('1:1')
                                        ->imageResizeTargetWidth('800')
                                        ->imageResizeTargetHeight('800')
                                        ->columnSpanFull()
                                        ->alignCenter()
                                        ->extraInputAttributes(['capture' => 'user'])
                                        ->helperText('📱 Clique para tirar foto ou selecione do arquivo (Opcional)'),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nome Completo')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2)
                                            ->prefixIcon('heroicon-o-user')
                                            ->placeholder('Digite o nome completo'),

                                        Forms\Components\TextInput::make('cpf')
                                            ->label('CPF')
                                            ->mask('999.999.999-99')
                                            ->unique(User::class, 'cpf', ignoreRecord: true)
                                            ->required()
                                            ->prefixIcon('heroicon-o-identification')
                                            ->placeholder('000.000.000-00'),
                                    ]),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('email')
                                            ->label('E-mail')
                                            ->email()
                                            ->required()
                                            ->unique(User::class, 'email', ignoreRecord: true)
                                            ->prefixIcon('heroicon-o-envelope')
                                            ->placeholder('exemplo@email.com'),

                                        Forms\Components\TextInput::make('telefone')
                                            ->label('Telefone/WhatsApp')
                                            ->mask('(99) 99999-9999')
                                            ->required()
                                            ->prefixIcon('heroicon-o-phone')
                                            ->placeholder('(00) 00000-0000'),

                                        Forms\Components\DatePicker::make('data_nasc')
                                            ->label('Data de Nascimento')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->prefixIcon('heroicon-o-cake')
                                            ->placeholder('Selecione a data'),
                                    ]),

                                    Forms\Components\Hidden::make('nivel')->default('aluno'),
                                    Forms\Components\Hidden::make('password')->default(fn() => bcrypt('aluno@123')),
                                    Forms\Components\Hidden::make('ativo')->default(true),
                                ])
                        ])
                        ->columns(1),

                    // ===== ETAPA 2 =====
                    Forms\Components\Wizard\Step::make('🏠 Endereço')
                        ->description('Dados de localização')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\Section::make()
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
                                                try {
                                                    $response = Http::timeout(5)->get("https://viacep.com.br/ws/$cep/json/")->json();
                                                    if (!isset($response['erro'])) {
                                                        $set('endereco', $response['logradouro'] ?? '');
                                                        $set('bairro', $response['bairro'] ?? '');
                                                        $set('cidade', $response['localidade'] ?? '');
                                                        $set('estado', $response['uf'] ?? '');
                                                    }
                                                } catch (\Exception $e) {}
                                            })
                                            ->columnSpan(2)
                                            ->placeholder('00000-000'),

                                        Forms\Components\TextInput::make('numero')
                                            ->label('Número')
                                            ->columnSpan(1)
                                            ->placeholder('123'),

                                        Forms\Components\TextInput::make('complemento')
                                            ->label('Complemento')
                                            ->columnSpan(1)
                                            ->placeholder('Apto, Bloco, etc'),
                                    ]),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('endereco')
                                            ->label('Logradouro')
                                            ->columnSpan(2)
                                            ->placeholder('Rua, Avenida, etc'),

                                        Forms\Components\TextInput::make('bairro')
                                            ->label('Bairro')
                                            ->placeholder('Nome do bairro'),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('cidade')
                                            ->label('Cidade')
                                            ->placeholder('Nome da cidade'),

                                        Forms\Components\Select::make('estado')
                                            ->label('Estado (UF)')
                                            ->options([
                                                'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                                'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal',
                                                'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão',
                                                'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                                                'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco',
                                                'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                                'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima',
                                                'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe',
                                                'TO' => 'Tocantins',
                                            ])
                                            ->searchable()
                                            ->native(false)
                                            ->placeholder('Selecione'),
                                    ]),

                                    Forms\Components\Placeholder::make('info_pular')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="text-sm text-gray-500 italic">
                                                💡 Estes dados podem ser preenchidos depois
                                            </div>
                                        ')),
                                ])
                        ])
                        ->columns(1),

                    // ===== ETAPA 3 =====
                    Forms\Components\Wizard\Step::make('📄 Documentos')
                        ->description('Anexe os documentos necessários')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('info_documentos')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="text-3xl">📋</div>
                                                    <div>
                                                        <div class="font-bold text-blue-700 text-lg">Documentos Necessários</div>
                                                        <div class="text-sm text-gray-600 mt-1">
                                                            Adicione os documentos abaixo. É possível pular esta etapa e enviar depois.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ')),

                                    Forms\Components\Repeater::make('documentos')
                                        ->label('')
                                        ->columnSpanFull()
                                        ->schema([
                                            Forms\Components\Grid::make(12)->schema([
                                                Forms\Components\Select::make('tipo')
                                                    ->label('Tipo de Documento')
                                                    ->options([
                                                        'RG'              => '🪪 RG (Identidade)',
                                                        'CPF'             => '📜 CPF',
                                                        'COMP_RESIDENCIA' => '🏠 Comp. Residência',
                                                        'CERT_NASC_CAS'   => '👶 Certidão Nasc/Casamento',
                                                        'HISTORICO'       => '🎓 Histórico Escolar',
                                                        'DIPLOMA'         => '📜 Diploma',
                                                        'FOTO_3X4'        => '📸 Foto 3x4',
                                                        'OUTROS'          => '📁 Outros',
                                                    ])
                                                    ->required()
                                                    ->native(false)
                                                    ->searchable()
                                                    ->columnSpan(3)
                                                    ->placeholder('Selecione o tipo'),

                                                Forms\Components\DatePicker::make('data_emissao')
                                                    ->label('Emissão')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->maxDate(now())
                                                    ->columnSpan(2)
                                                    ->placeholder('dd/mm/aaaa'),

                                                Forms\Components\FileUpload::make('arquivo')
                                                    ->label('Arquivo (PDF ou Imagem)')
                                                    ->disk('public')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxSize(5120)
                                                    ->downloadable()
                                                    ->openable()
                                                    ->previewable(true)
                                                    ->columnSpan(5)
                                                    ->helperText('Máx. 5MB')
                                                    ->directory('alunos/documentos'),

                                                Forms\Components\Placeholder::make('status_doc')
                                                    ->label('Status')
                                                    ->content(function ($get) {
                                                        $arquivo = $get('arquivo');
                                                        if (is_array($arquivo)) {
                                                            $arquivo = array_values($arquivo)[0] ?? null;
                                                        }
                                                        if (!$arquivo) {
                                                            return new \Illuminate\Support\HtmlString('
                                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                                                                    ⏳ Pendente
                                                                </span>
                                                            ');
                                                        }
                                                        return new \Illuminate\Support\HtmlString('
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                                ✅ Enviado
                                                            </span>
                                                        ');
                                                    })
                                                    ->columnSpan(2),
                                            ]),

                                            Forms\Components\Textarea::make('observacoes')
                                                ->label('Observações')
                                                ->rows(2)
                                                ->placeholder('Informações adicionais sobre este documento')
                                                ->columnSpanFull(),
                                        ])
                                        ->itemLabel(function (array $state): ?string {
                                            if (empty($state['tipo'])) return '📄 Novo Documento';
                                            $tipos = [
                                                'RG'              => '🪪 RG',
                                                'CPF'             => '📜 CPF',
                                                'COMP_RESIDENCIA' => '🏠 Comp. Residência',
                                                'CERT_NASC_CAS'   => '👶 Certidão',
                                                'HISTORICO'       => '🎓 Histórico',
                                                'DIPLOMA'         => '📜 Diploma',
                                                'FOTO_3X4'        => '📸 Foto 3x4',
                                                'OUTROS'          => '📁 Outros',
                                            ];
                                            $label      = $tipos[$state['tipo']] ?? $state['tipo'];
                                            $hasFile    = !empty($state['arquivo']);
                                            $statusIcon = $hasFile ? '✅' : '⏳';
                                            return $statusIcon . ' ' . $label;
                                        })
                                        ->collapsible()
                                        ->collapsed(fn($state) => !empty($state['arquivo']))
                                        ->cloneable()
                                        ->reorderableWithButtons()
                                        ->addActionLabel('➕ Adicionar Documento')
                                        ->defaultItems(0)
                                        ->grid(1),

                                    Forms\Components\Placeholder::make('resumo_docs')
                                        ->label('')
                                        ->content(function ($get) {
                                            $docs = $get('documentos') ?? [];
                                            if (empty($docs)) {
                                                return new \Illuminate\Support\HtmlString('
                                                    <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                                        <div class="text-4xl mb-2">📭</div>
                                                        <div class="text-gray-600">Nenhum documento adicionado</div>
                                                        <div class="text-xs text-gray-400 mt-2">Clique em "Adicionar Documento" acima</div>
                                                    </div>
                                                ');
                                            }
                                            $total    = count($docs);
                                            $enviados = collect($docs)->filter(function ($doc) {
                                                $arquivo = $doc['arquivo'] ?? null;
                                                if (is_array($arquivo)) $arquivo = array_values($arquivo)[0] ?? null;
                                                return !empty($arquivo);
                                            })->count();
                                            $pendentes = $total - $enviados;
                                            $progresso = $total > 0 ? round(($enviados / $total) * 100) : 0;
                                            $cor       = $progresso == 100 ? 'green' : 'yellow';
                                            return new \Illuminate\Support\HtmlString("
                                                <div class='bg-{$cor}-50 border-2 border-{$cor}-200 rounded-lg p-4 mt-4'>
                                                    <div class='flex items-center justify-between mb-3'>
                                                        <div class='flex items-center gap-3'>
                                                            <div class='text-3xl'>" . ($progresso == 100 ? '✅' : '📋') . "</div>
                                                            <div>
                                                                <div class='font-bold text-{$cor}-700 text-lg'>{$enviados} de {$total} documentos</div>
                                                                <div class='text-sm text-gray-600'>" . ($pendentes > 0 ? "⏳ {$pendentes} pendente(s)" : "🎉 Todos enviados!") . "</div>
                                                            </div>
                                                        </div>
                                                        <div class='text-4xl font-bold text-{$cor}-700'>{$progresso}%</div>
                                                    </div>
                                                    <div class='w-full bg-gray-200 rounded-full h-3'>
                                                        <div class='bg-{$cor}-500 h-3 rounded-full transition-all' style='width: {$progresso}%'></div>
                                                    </div>
                                                </div>
                                            ");
                                        }),
                                ])
                        ])
                        ->columns(1),

                    // ===== ETAPA 4 =====
                    Forms\Components\Wizard\Step::make('👨‍👩‍👧‍👦 Responsável')
                        ->description('Dados do responsável legal')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('responsavel_nome')
                                            ->label('Nome do Responsável')
                                            ->maxLength(255)
                                            ->placeholder('Nome completo do responsável'),

                                        Forms\Components\TextInput::make('responsavel_telefone')
                                            ->label('Telefone do Responsável')
                                            ->mask('(99) 99999-9999')
                                            ->placeholder('(00) 00000-0000'),
                                    ]),

                                    Forms\Components\Textarea::make('observacoes_aluno')
                                        ->label('Observações Gerais')
                                        ->rows(3)
                                        ->placeholder('Informações adicionais sobre o aluno...')
                                        ->extraAttributes(['x-load' => 'true'])
                                        ->columnSpanFull(),

                                    Forms\Components\Placeholder::make('info_resp')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString('
                                            <div class="text-sm text-gray-500 italic mt-2">
                                                💡 Estes dados são opcionais e podem ser preenchidos depois
                                            </div>
                                        ')),
                                ])
                        ])
                        ->columns(1),

                    // ===== ETAPA 5 =====
                    Forms\Components\Wizard\Step::make('🎓 Matrícula')
                        ->description('Vincule o aluno a um curso')
                        ->icon('heroicon-o-academic-cap')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Select::make('curso_id')
                                            ->label('Curso')
                                            ->options(Curso::where('ativo', true)->pluck('titulo', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->preload()
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(fn(callable $set) => $set('turma_id', null))
                                            ->prefixIcon('heroicon-o-academic-cap')
                                            ->placeholder('Selecione o curso'),

                                        Forms\Components\Select::make('turma_id')
                                            ->label('Turma')
                                            ->options(function (Get $get) {
                                                $cursoId = $get('curso_id');
                                                if (!$cursoId) return [];
                                                return Turma::where('ativa', true)
                                                    ->where('curso_id', $cursoId)
                                                    ->pluck('nome', 'id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->preload()
                                            ->native(false)
                                            ->disabled(fn(Get $get) => !$get('curso_id'))
                                            ->prefixIcon('heroicon-o-user-group')
                                            ->placeholder('Selecione a turma'),

                                        Forms\Components\DatePicker::make('data_matricula')
                                            ->label('Data da Matrícula')
                                            ->default(now())
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->prefixIcon('heroicon-o-calendar'),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('dia_vencimento')
                                            ->label('Dia do Vencimento')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(31)
                                            ->default(10)
                                            ->required()
                                            ->suffix('do mês')
                                            ->helperText('Dia que vence a mensalidade'),

                                        Forms\Components\Select::make('status_matricula')
                                            ->label('Status da Matrícula')
                                            ->options([
                                                'ativo'    => '✅ Ativo',
                                                'trancado' => '⏸ Trancado',
                                            ])
                                            ->default('ativo')
                                            ->required()
                                            ->native(false),
                                    ]),

                                    Forms\Components\Textarea::make('observacoes_matricula')
                                        ->label('Observações da Matrícula')
                                        ->rows(2)
                                        ->placeholder('Informações adicionais sobre a matrícula...')
                                        ->extraAttributes(['x-load' => 'true'])
                                        ->columnSpanFull(),
                                ])
                        ])
                        ->columns(1),

                    // ===== ETAPA 6 =====
                    Forms\Components\Wizard\Step::make('💰 Mensalidades')
                        ->description('Configure o pagamento')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('valor_mensalidade')
                                            ->label('Valor da Mensalidade')
                                            ->numeric()
                                            ->required()
                                            ->prefix('R$')
                                            ->step(0.01)
                                            ->minValue(0.01)
                                            ->live()
                                            ->helperText('Valor de cada parcela'),

                                        Forms\Components\TextInput::make('qtd_parcelas')
                                            ->label('Quantidade de Parcelas')
                                            ->numeric()
                                            ->required()
                                            ->default(12)
                                            ->minValue(1)
                                            ->maxValue(48)
                                            ->suffix('parcelas')
                                            ->live(),

                                        Forms\Components\Placeholder::make('valor_total_calc')
                                            ->label('Valor Total do Curso')
                                            ->content(function (Get $get) {
                                                $valor = $get('valor_mensalidade') ?? 0;
                                                $qtd   = $get('qtd_parcelas') ?? 1;
                                                $total = $valor * $qtd;
                                                return 'R$ ' . number_format($total, 2, ',', '.');
                                            }),
                                    ]),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\DatePicker::make('primeira_mensalidade')
                                            ->label('Vencimento da 1ª Mensalidade')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->default(function () {
                                                $hoje = now();
                                                if ($hoje->day <= 10) {
                                                    return $hoje->copy()->day(10);
                                                } else {
                                                    return $hoje->copy()->addMonth()->day(10);
                                                }
                                            })
                                            ->helperText('As demais serão geradas automaticamente'),

                                        Forms\Components\Toggle::make('gerar_todas_pendentes')
                                            ->label('Gerar todas como Pendentes')
                                            ->default(true)
                                            ->inline(false)
                                            ->helperText('Se desativado, mensalidades vencidas serão marcadas como Atrasadas'),
                                    ]),

                                    Forms\Components\Placeholder::make('info_final')
                                        ->label('')
                                        ->content(fn(Get $get) => new \Illuminate\Support\HtmlString("
                                            <div class='bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mt-4'>
                                                <div class='flex items-center gap-3'>
                                                    <div class='text-3xl'>📊</div>
                                                    <div>
                                                        <div class='font-bold text-blue-700'>Resumo</div>
                                                        <div class='text-sm text-gray-600 mt-1'>
                                                            Serão geradas <strong>" . ($get('qtd_parcelas') ?? 0) . " mensalidades</strong> de
                                                            <strong>R$ " . number_format($get('valor_mensalidade') ?? 0, 2, ',', '.') . "</strong> cada
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ")),
                                ])
                        ])
                        ->columns(1),

                ])
                ->columnSpanFull()
                ->persistStepInQueryString()

                /*
                |--------------------------------------------------------------------------
                | FIX: Tecla Enter avança para a próxima etapa do Wizard
                |--------------------------------------------------------------------------
                | SOLUÇÃO DEFINITIVA:
                |
                | O x-init é avaliado pelo Alpine no contexto do componente wizard,
                | portanto 'this' aqui é o proxy Alpine que expõe nextStep().
                |
                | O problema das tentativas anteriores: o callback do addEventListener
                | é uma função JS comum que perde o escopo Alpine. A solução é capturar
                | 'this' em uma variável (wizardProxy) ANTES do callback, e usá-la dentro.
                */
                ->extraAttributes([
                    'x-init' => "
                        var wizardProxy = this;
                        \$el.addEventListener('keydown', function(ev) {
                            if (ev.key !== 'Enter') return;
                            if (ev.target.tagName === 'TEXTAREA') return;
                            if (ev.target.tagName === 'SELECT') return;
                            if (ev.target.closest('[role=\"dialog\"]')) return;

                            ev.preventDefault();
                            ev.stopPropagation();

                            if (typeof wizardProxy.nextStep === 'function') {
                                wizardProxy.nextStep();
                            }
                        });
                    ",
                ])

                ->submitAction(new \Illuminate\Support\HtmlString('
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-all shadow-lg">
                        ✅ Finalizar Cadastro
                    </button>
                ')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('nivel', 'aluno')
                    ->with(['matriculas.curso', 'matriculas.turma'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->email),

                Tables\Columns\TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telefone')
                    ->label('Telefone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('matriculas_count')
                    ->label('Matrículas')
                    ->counts('matriculas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->label('Cadastrados Hoje')
                    ->query(fn($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_detalhes')
                    ->label('Ver Detalhes')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn($record) => route('filament.secretario.resources.aluno-secretarias.edit', $record)),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Nenhum cadastro rápido ainda')
            ->emptyStateDescription('Use o botão acima para fazer o primeiro cadastro')
            ->emptyStateIcon('heroicon-o-bolt');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCadastroRapido::route('/'),
            'create' => Pages\CreateCadastroRapido::route('/create'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $hoje = User::where('nivel', 'aluno')
            ->whereDate('created_at', today())
            ->count();
        return $hoje > 0 ? (string)$hoje : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}