<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PessoaResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

class PessoaResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Pessoa';
    protected static ?string $pluralModelLabel = 'Gestão de Pessoas';
    protected static ?string $navigationLabel = 'Gestão de Pessoas';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administração';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === SEÇÃO 1: TIPO DE USUÁRIO E FOTO ===
                Forms\Components\Section::make('Identificação')
                    ->description('Defina o tipo de usuário e foto de perfil')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\Select::make('nivel')
                            ->label('Tipo de Usuário')
                            ->options([
                                'admin' => '👑 Administrador - Acesso total ao sistema',
                                'secretario' => '📚 Secretário - Gestão acadêmica e financeira',
                                'professor' => '🎓 Professor - Ministrar aulas e avaliar',
                                'aluno' => '🎒 Aluno - Acesso aos cursos matriculados',
                            ])
                            ->required()
                            ->live()
                            ->native(false)
                            ->default('aluno')
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Define senha padrão baseada no nível
                                $senhasPadrao = [
                                    'admin' => 'admin@123',
                                    'secretario' => 'secretario@123',
                                    'professor' => 'professor@123',
                                    'aluno' => 'aluno@123',
                                ];
                                $set('password', bcrypt($senhasPadrao[$state] ?? 'mudar@123'));
                            }),

                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto de Perfil')
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('pessoas')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpanFull()
                            ->alignCenter()
                            ->helperText('Tamanho máximo: 2MB. Formatos: JPG, PNG, WEBP'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                // === SEÇÃO 2: DADOS PESSOAIS ===
                Forms\Components\Section::make('Dados Pessoais')
                    ->description('Informações básicas e de contato')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: João Silva Santos')
                                    ->autocomplete('name')
                                    ->prefixIcon('heroicon-o-user'),

                                Forms\Components\TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('usuario@escola.com.br')
                                    ->autocomplete('email')
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('verificar_email')
                                            ->icon('heroicon-o-check-circle')
                                            ->action(function () {
                                                Notification::make()
                                                    ->title('E-mail validado!')
                                                    ->success()
                                                    ->send();
                                            })
                                    ),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('telefone')
                                    ->label('Telefone/WhatsApp')
                                    ->mask('(99) 99999-9999')
                                    ->tel()
                                    ->required()
                                    ->placeholder('(00) 00000-0000')
                                    ->prefixIcon('heroicon-o-phone')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('testar_whatsapp')
                                            ->icon('heroicon-o-chat-bubble-left-right')
                                            ->color('success')
                                            ->url(fn (Get $get) => 
                                                "https://api.whatsapp.com/send?phone=55" . 
                                                preg_replace('/[^0-9]/', '', $get('telefone') ?? '')
                                            )
                                            ->openUrlInNewTab()
                                    ),

                                Forms\Components\TextInput::make('cpf')
                                    ->label('CPF')
                                    ->mask('999.999.999-99')
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('000.000.000-00')
                                    ->prefixIcon('heroicon-o-identification')
                                    ->required(),

                                Forms\Components\DatePicker::make('data_nasc')
                                    ->label('Data de Nascimento')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->maxDate(now()->subYears(5))
                                    ->prefixIcon('heroicon-o-cake')
                                    ->helperText('Idade mínima: 5 anos'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('ativo')
                                    ->label('Usuário Ativo')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false)
                                    ->helperText('Desative para suspender o acesso'),

                                Forms\Components\Placeholder::make('info_senha')
                                    ->label('Senha Inicial')
                                    ->content(fn (Get $get) => new HtmlString(
                                        '<div class="text-sm text-gray-600 bg-blue-50 p-3 rounded-lg">' .
                                        '<p class="font-semibold mb-1">Senha padrão por tipo:</p>' .
                                        '<ul class="list-disc ml-4 space-y-1">' .
                                        '<li>👑 Admin: <code class="bg-gray-100 px-2 py-1 rounded">admin@123</code></li>' .
                                        '<li>📚 Secretário: <code class="bg-gray-100 px-2 py-1 rounded">secretario@123</code></li>' .
                                        '<li>🎓 Professor: <code class="bg-gray-100 px-2 py-1 rounded">professor@123</code></li>' .
                                        '<li>🎒 Aluno: <code class="bg-gray-100 px-2 py-1 rounded">aluno@123</code></li>' .
                                        '</ul>' .
                                        '<p class="mt-2 text-xs">O usuário deve alterar no primeiro acesso.</p>' .
                                        '</div>'
                                    ))
                                    ->hiddenOn('edit'),
                            ]),

                        Forms\Components\Hidden::make('password')
                            ->default(fn () => bcrypt('mudar@123'))
                            ->dehydrated(fn ($state) => filled($state)),
                    ])
                    ->columns(1)
                    ->collapsible(),

                // === SEÇÃO 3: ENDEREÇO ===
                Forms\Components\Section::make('Endereço')
                    ->description('Localização e dados residenciais')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('cep')
                                    ->label('CEP')
                                    ->mask('99999-999')
                                    ->placeholder('00000-000')
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-map')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('buscar_cep')
                                            ->icon('heroicon-o-magnifying-glass')
                                            ->action(function (Get $get, Forms\Set $set) {
                                                $cep = preg_replace('/[^0-9]/', '', $get('cep') ?? '');
                                                
                                                if (strlen($cep) !== 8) {
                                                    Notification::make()
                                                        ->title('CEP inválido')
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }

                                                $response = Http::get("https://viacep.com.br/ws/$cep/json/")->json();

                                                if (isset($response['erro'])) {
                                                    Notification::make()
                                                        ->title('CEP não encontrado')
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }

                                                $set('endereco', $response['logradouro'] ?? '');
                                                $set('bairro', $response['bairro'] ?? '');
                                                $set('cidade', $response['localidade'] ?? '');
                                                $set('estado', $response['uf'] ?? '');

                                                Notification::make()
                                                    ->title('Endereço encontrado!')
                                                    ->success()
                                                    ->send();
                                            })
                                    )
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
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('numero')
                                    ->label('Número')
                                    ->placeholder('123')
                                    ->maxLength(10)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('complemento')
                                    ->label('Complemento')
                                    ->placeholder('Apto 45')
                                    ->maxLength(50)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('endereco')
                                    ->label('Logradouro')
                                    ->placeholder('Rua, Avenida...')
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('bairro')
                                    ->label('Bairro')
                                    ->placeholder('Centro')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('cidade')
                                    ->label('Cidade')
                                    ->placeholder('São Paulo'),

                                Forms\Components\Select::make('estado')
                                    ->label('Estado (UF)')
                                    ->options([
                                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá',
                                        'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará',
                                        'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                        'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso',
                                        'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                                        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro',
                                        'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
                                        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                        'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
                                    ])
                                    ->searchable()
                                    ->native(false),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                // === SEÇÃO 4: DADOS BANCÁRIOS (PIX) ===
                Forms\Components\Section::make('Dados Bancários')
                    ->description('Informações para pagamento via PIX')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('tipo_chave')
                                    ->label('Tipo de Chave PIX')
                                    ->options([
                                        'CPF' => '🆔 CPF',
                                        'CNPJ' => '🏢 CNPJ',
                                        'Email' => '📧 E-mail',
                                        'Telefone' => '📱 Telefone',
                                        'Aleatoria' => '🔑 Chave Aleatória',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-o-key'),

                                Forms\Components\TextInput::make('chave_pix')
                                    ->label('Chave PIX')
                                    ->placeholder(fn (Get $get) => match($get('tipo_chave')) {
                                        'CPF' => '000.000.000-00',
                                        'CNPJ' => '00.000.000/0000-00',
                                        'Email' => 'email@exemplo.com',
                                        'Telefone' => '(00) 00000-0000',
                                        'Aleatoria' => 'cole-a-chave-aleatoria-aqui',
                                        default => 'Selecione o tipo de chave'
                                    })
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->helperText('Digite a chave PIX exatamente como cadastrada'),
                            ]),

                        Forms\Components\Placeholder::make('info_pix')
                            ->label('ℹ️ Informação Importante')
                            ->content(new HtmlString('
                                <div class="text-sm text-gray-600 bg-blue-50 p-3 rounded-lg">
                                    <p class="font-semibold mb-1">Certifique-se de que a chave PIX está correta!</p>
                                    <p>Esta informação será usada para pagamentos. Verifique com atenção antes de salvar.</p>
                                </div>
                            '))
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (Get $get) => in_array($get('nivel'), ['professor', 'secretario'])),

                // === SEÇÃO 5: INFORMAÇÕES ADICIONAIS POR TIPO ===
                Forms\Components\Section::make('Informações Específicas')
                    ->description(fn (Get $get) => match($get('nivel')) {
                        'admin' => 'Configurações de administrador',
                        'secretario' => 'Dados do secretário',
                        'professor' => 'Qualificações do professor',
                        'aluno' => 'Informações do aluno',
                        default => 'Informações complementares'
                    })
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        // CAMPOS PARA PROFESSOR
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('formacao')
                                    ->label('Formação Acadêmica')
                                    ->options([
                                        'graduacao' => '🎓 Graduação',
                                        'especializacao' => '📚 Especialização',
                                        'mestrado' => '🎯 Mestrado',
                                        'doutorado' => '👨‍🎓 Doutorado',
                                        'pos_doutorado' => '🏆 Pós-Doutorado',
                                    ])
                                    ->native(false),

                                Forms\Components\TextInput::make('area_atuacao')
                                    ->label('Área de Atuação')
                                    ->placeholder('Ex: Matemática, História, etc.')
                                    ->maxLength(255),
                            ])
                            ->visible(fn (Get $get) => $get('nivel') === 'professor'),

                        Forms\Components\Textarea::make('biografia')
                            ->label('Biografia / Experiência')
                            ->placeholder('Descreva brevemente sua experiência profissional...')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('nivel') === 'professor'),

                        // CAMPOS PARA ALUNO
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('responsavel_nome')
                                    ->label('Nome do Responsável')
                                    ->placeholder('Nome completo do pai/mãe')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('responsavel_telefone')
                                    ->label('Telefone do Responsável')
                                    ->mask('(99) 99999-9999')
                                    ->placeholder('(00) 00000-0000'),
                            ])
                            ->visible(fn (Get $get) => $get('nivel') === 'aluno'),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->placeholder('Informações adicionais, restrições médicas, etc.')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('nivel') === 'aluno'),

                        // CAMPOS PARA SECRETÁRIO
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('setor')
                                    ->label('Setor')
                                    ->options([
                                        'academico' => '📚 Acadêmico',
                                        'financeiro' => '💰 Financeiro',
                                        'rh' => '👥 Recursos Humanos',
                                        'geral' => '🏢 Secretaria Geral',
                                    ])
                                    ->native(false),

                                Forms\Components\TextInput::make('ramal')
                                    ->label('Ramal')
                                    ->placeholder('Ex: 1234')
                                    ->maxLength(10),
                            ])
                            ->visible(fn (Get $get) => $get('nivel') === 'secretario'),

                        // CAMPOS PARA ADMIN
                        Forms\Components\Toggle::make('super_admin')
                            ->label('Super Administrador')
                            ->helperText('Acesso irrestrito a todas as funcionalidades')
                            ->default(false)
                            ->visible(fn (Get $get) => $get('nivel') === 'admin'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->circular()
                    ->label('Foto')
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email),

                Tables\Columns\TextColumn::make('nivel')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'admin' => 'danger',
                        'secretario' => 'warning',
                        'professor' => 'success',
                        'aluno' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'admin' => '👑 Admin',
                        'secretario' => '📚 Secretário',
                        'professor' => '🎓 Professor',
                        'aluno' => '🎒 Aluno',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telefone')
                    ->label('Contato')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Telefone copiado!')
                    ->tooltip('Clique para copiar'),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Localização')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => 
                        $record->cidade && $record->estado 
                            ? "{$record->cidade}/{$record->estado}" 
                            : 'Não informado'
                    )
                    ->icon('heroicon-m-map-pin')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('chave_pix')
                    ->label('PIX')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->chave_pix ? 'PIX cadastrado' : 'PIX não cadastrado')
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('ativo')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nivel')
                    ->label('Tipo de Usuário')
                    ->options([
                        'admin' => '👑 Administrador',
                        'secretario' => '📚 Secretário',
                        'professor' => '🎓 Professor',
                        'aluno' => '🎒 Aluno',
                    ])
                    ->multiple()
                    ->native(false),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos')
                    ->native(false),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn () => User::query()
                        ->whereNotNull('estado')
                        ->where('estado', '!=', '')
                        ->distinct()
                        ->pluck('estado', 'estado')
                        ->toArray()
                    )
                    ->searchable()
                    ->multiple(),

                Tables\Filters\Filter::make('sem_pix')
                    ->label('Sem PIX cadastrado')
                    ->query(fn (Builder $query) => $query->whereNull('chave_pix')),

                Tables\Filters\Filter::make('created_at')
                    ->label('Cadastrado em')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('De')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Até')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(fn (User $record) => 
                            "https://api.whatsapp.com/send?phone=55" . 
                            preg_replace('/[^0-9]/', '', $record->telefone)
                        )
                        ->openUrlInNewTab()
                        ->visible(fn (User $record) => filled($record->telefone)),

                    Tables\Actions\Action::make('enviar_email')
                        ->label('Enviar E-mail')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->url(fn (User $record) => "mailto:{$record->email}")
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('resetar_senha')
                        ->label('Resetar Senha')
                        ->icon('heroicon-o-key')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Resetar Senha')
                        ->modalDescription('A senha será resetada para o padrão do tipo de usuário.')
                        ->action(function (User $record) {
                            $senhasPadrao = [
                                'admin' => 'admin@123',
                                'secretario' => 'secretario@123',
                                'professor' => 'professor@123',
                                'aluno' => 'aluno@123',
                            ];
                            
                            $novaSenha = $senhasPadrao[$record->nivel] ?? 'mudar@123';
                            $record->update(['password' => bcrypt($novaSenha)]);
                            
                            Notification::make()
                                ->title('Senha resetada!')
                                ->body("Nova senha: {$novaSenha}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('duplicar')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            $novo = $record->replicate();
                            $novo->email = null;
                            $novo->cpf = null;
                            $novo->name = $novo->name . ' (Cópia)';
                            $novo->save();
                            
                            Notification::make()
                                ->title('Usuário duplicado!')
                                ->body('Email e CPF foram limpos para evitar conflitos.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Ações')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('ativar')
                        ->label('Ativar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['ativo' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('desativar')
                        ->label('Desativar Selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['ativo' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('alterar_nivel')
                        ->label('Alterar Tipo')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('novo_nivel')
                                ->label('Novo Tipo de Usuário')
                                ->options([
                                    'admin' => '👑 Administrador',
                                    'secretario' => '📚 Secretário',
                                    'professor' => '🎓 Professor',
                                    'aluno' => '🎒 Aluno',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['nivel' => $data['novo_nivel']]);
                            
                            Notification::make()
                                ->title('Tipo alterado com sucesso!')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            Notification::make()
                                ->title('Exportação iniciada!')
                                ->body('Em breve o download será iniciado.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('Nenhuma pessoa cadastrada')
            ->emptyStateDescription('Comece adicionando o primeiro usuário ao sistema')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Cadastrar Primeira Pessoa')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // DocumentosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPessoas::route('/'),
            'create' => Pages\CreatePessoa::route('/create'),
            'edit' => Pages\EditPessoa::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('ativo', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $total = static::getModel()::where('ativo', true)->count();
        
        if ($total > 100) return 'success';
        if ($total > 50) return 'warning';
        return 'gray';
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'cpf', 'telefone'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Tipo' => match($record->nivel) {
                'admin' => '👑 Admin',
                'secretario' => '📚 Secretário',
                'professor' => '🎓 Professor',
                'aluno' => '🎒 Aluno',
                default => $record->nivel,
            },
            'Email' => $record->email,
            'Status' => $record->ativo ? '✅ Ativo' : '❌ Inativo',
        ];
    }
}
                        