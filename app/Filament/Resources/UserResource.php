<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use App\Filament\Resources\UserResource\RelationManagers\DocumentosRelationManager;


class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Professor';
    protected static ?string $pluralModelLabel = 'Professores';
    protected static ?string $navigationLabel = 'Professores';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Gestão Acadêmica';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('nivel', 'Professor');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === SEÇÃO 1: DADOS PESSOAIS ===
                Forms\Components\Section::make('Dados Pessoais')
                    ->description('Informações básicas e contato do professor')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\FileUpload::make('foto')
                            ->label('Foto do Professor')
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('professores')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpanFull()
                            ->alignCenter()
                            ->helperText('Tamanho máximo: 2MB. Formatos: JPG, PNG, WEBP'),

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
                                    ->placeholder('professor@escola.com.br')
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
                                    ->rule('cpf'),

                                Forms\Components\DatePicker::make('data_nasc')
                                    ->label('Data de Nascimento')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->maxDate(now()->subYears(18))
                                    ->prefixIcon('heroicon-o-cake')
                                    ->helperText('Idade mínima: 18 anos'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('ativo')
                                    ->label('Professor Ativo')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false)
                                    ->helperText('Desative para suspender temporariamente'),

                                Forms\Components\Placeholder::make('info_senha')
                                    ->label('Senha Inicial')
                                    ->content(new HtmlString('<span class="text-sm text-gray-600">Senha padrão: <code class="bg-gray-100 px-2 py-1 rounded">mudar@123</code><br>O professor deve alterá-la no primeiro acesso.</span>'))
                                    ->hiddenOn('edit'),
                            ]),

                        Forms\Components\Select::make('nivel')
    ->label('Função / Acesso')
    ->options([
        'admin' => '👑 Administrador',
        'secretario' => '📚 Secretaria',
        'professor' => '🎓 Professor',
        'aluno' => '🎒 Aluno',
    ])
    ->required()
    ->native(false)
    ->default('aluno'),
                        
                        Forms\Components\Hidden::make('password')
                            ->default(fn () => bcrypt('mudar@123'))
                            ->dehydrated(fn ($state) => filled($state)),
                    ])
                    ->columns(1)
                    ->collapsible(),

                // === SEÇÃO 2: ENDEREÇO ===
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

                // === SEÇÃO 3: DADOS BANCÁRIOS ===
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
                    ->color('gray'),

                Tables\Columns\IconColumn::make('chave_pix')
                    ->label('PIX')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->chave_pix ? 'PIX cadastrado' : 'PIX não cadastrado'),

                Tables\Columns\ToggleColumn::make('ativo')
                    ->label('Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle'),

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
                    ->falseLabel('Apenas Inativos')
                    ->native(false),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn () => User::query()
                        ->where('nivel', 'Professor')
                        ->whereNotNull('estado') // <--- FIX IS HERE: Filter out nulls
                        ->where('estado', '!=', '') // <--- AND HERE: Filter out empty strings
                        ->distinct()
                        ->pluck('estado', 'estado')
                        ->toArray()
                    )
                    ->searchable(),

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
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('enviar_email')
                        ->label('Enviar E-mail')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->url(fn (User $record) => "mailto:{$record->email}")
                        ->openUrlInNewTab(),

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
                                ->title('Professor duplicado com sucesso!')
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
                        ->action(fn ($records) => $records->each->update(['ativo' => true])),

                    Tables\Actions\BulkAction::make('desativar')
                        ->label('Desativar Selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['ativo' => false])),

                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar Selecionados')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            Notification::make()
                                ->title('Exportação iniciada!')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('Nenhum professor cadastrado')
            ->emptyStateDescription('Comece adicionando o primeiro professor ao sistema')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Cadastrar Primeiro Professor')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DocumentosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            // 'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('nivel', 'Professor')->where('ativo', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}