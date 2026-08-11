<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use App\Models\CourseModality;
use App\Services\AprovarInscricaoException;
use App\Services\AprovarInscricaoService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * ApplicationResource - Recurso de Inscrições no Filament
 *
 * Fluxo oficial: Candidato se inscreve → Secretaria analisa → Aprova+Matricula/Rejeita
 *
 * Regras importantes desta versão:
 *  - A ação "Aprovar e matricular" é a ÚNICA porta de entrada para virar matrícula.
 *  - O campo `status` do formulário comum é somente leitura para não contornar o fluxo.
 *  - Rejeição exige justificativa.
 *  - Apenas Secretaria e Administrador podem executar as ações.
 */
class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Secretaria';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Inscrição';

    protected static ?string $pluralLabel = 'Inscrições';

    protected static ?string $recordTitleAttribute = 'nome_completo';

    /**
     * Verifica se o usuário atual pode aprovar/rejeitar inscrições.
     * Segue os perfis existentes do sistema (`users.nivel`).
     */
    public static function usuarioPodeGerenciar(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }

        return in_array(
            strtolower((string) $u->nivel),
            ['admin', 'secretario', 'secretaria'],
            true,
        );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Pessoais')
                    ->description('Informações pessoais do candidato')
                    ->icon('heroicon-o-user')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('nome_completo')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cpf')
                            ->label('CPF')
                            ->required()
                            ->maxLength(14)
                            ->mask('999.999.999-99'),

                        Forms\Components\TextInput::make('rg')
                            ->label('RG')
                            ->nullable()
                            ->maxLength(20),

                        Forms\Components\DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),

                        Forms\Components\Select::make('sexo')
                            ->label('Sexo')
                            ->required()
                            ->options([
                                'M' => 'Masculino',
                                'F' => 'Feminino',
                                'Outro' => 'Outro',
                            ]),

                        Forms\Components\Select::make('estado_civil')
                            ->label('Estado Civil')
                            ->nullable()
                            ->options([
                                'solteiro' => 'Solteiro(a)',
                                'casado' => 'Casado(a)',
                                'divorciado' => 'Divorciado(a)',
                                'viuvo' => 'Viúvo(a)',
                                'uniao_estavel' => 'União Estável',
                            ]),

                        Forms\Components\TextInput::make('nacionalidade')
                            ->label('Nacionalidade')
                            ->default('Brasileira')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('naturalidade_cidade')
                            ->label('Naturalidade (Cidade)')
                            ->nullable()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('naturalidade_estado')
                            ->label('Naturalidade (Estado)')
                            ->nullable()
                            ->maxLength(2),
                    ]),

                Forms\Components\Section::make('Contato')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('telefone')
                            ->label('Telefone')
                            ->required()
                            ->tel()
                            ->maxLength(15)
                            ->mask('(99) 9999-9999'),

                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->nullable()
                            ->tel()
                            ->maxLength(15)
                            ->mask('(99) 99999-9999'),
                    ]),

                Forms\Components\Section::make('Endereço')
                    ->icon('heroicon-o-map-pin')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->required()
                            ->maxLength(9)
                            ->mask('99999-999'),

                        Forms\Components\TextInput::make('logradouro')
                            ->label('Logradouro')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('numero')
                            ->label('Número')
                            ->required()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('complemento')
                            ->label('Complemento')
                            ->nullable()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('bairro')
                            ->label('Bairro')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('cidade')
                            ->label('Cidade')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('estado')
                            ->label('Estado')
                            ->required()
                            ->maxLength(2),
                    ]),

                Forms\Components\Section::make('Dados Acadêmicos')
                    ->icon('heroicon-o-academic-cap')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('escolaridade')
                            ->label('Escolaridade')
                            ->required()
                            ->options([
                                'fundamental_incompleto' => 'Ensino Fundamental Incompleto',
                                'fundamental_completo' => 'Ensino Fundamental Completo',
                                'medio_incompleto' => 'Ensino Médio Incompleto',
                                'medio_completo' => 'Ensino Médio Completo',
                                'superior_incompleto' => 'Ensino Superior Incompleto',
                                'superior_completo' => 'Ensino Superior Completo',
                                'pos_graduacao' => 'Pós-Graduação',
                                'mestrado' => 'Mestrado',
                                'doutorado' => 'Doutorado',
                            ]),

                        Forms\Components\Select::make('curso_id')
                            ->label('Curso Pretendido')
                            ->required()
                            ->relationship('curso', 'titulo')
                            ->preload()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('course_modality_id', null);
                            }),

                        Forms\Components\Select::make('course_modality_id')
                            ->label('Modalidade')
                            ->required()
                            ->options(function ($get) {
                                $cursoId = $get('curso_id');
                                if (! $cursoId) {
                                    return [];
                                }

                                return CourseModality::where('curso_id', $cursoId)
                                    ->pluck('nome', 'id');
                            })
                            ->preload()
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Análise da Secretaria')
                    ->description('Somente leitura — use as ações "Aprovar e matricular" ou "Rejeitar".')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(1)
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                Application::STATUS_EM_ANALISE => 'Em Análise',
                                Application::STATUS_APROVADO => 'Aprovado',
                                Application::STATUS_REJEITADO => 'Rejeitado',
                                Application::STATUS_MATRICULADO => 'Matriculado',
                                Application::STATUS_CANCELADO => 'Cancelado',
                            ])
                            // Bloqueado no formulário comum para não contornar
                            // o fluxo oficial de aprovação/matrícula.
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('observacoes_secretaria')
                            ->label('Observações da Secretaria')
                            ->nullable()
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('analisado_por')
                            ->label('Analisado por')
                            ->content(fn ($record) => $record?->analisadoPor?->name ?? 'Não analisado'),

                        Forms\Components\Placeholder::make('analisado_em')
                            ->label('Analisado em')
                            ->content(fn ($record) => $record?->analisado_em?->format('d/m/Y H:i') ?? '-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome_completo')
                    ->label('Candidato')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cpf')
                    ->label('CPF')
                    ->formatStateUsing(fn ($state) => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->sortable(),

                Tables\Columns\TextColumn::make('courseModality.nome')
                    ->label('Modalidade')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => Application::STATUS_EM_ANALISE,
                        'success' => Application::STATUS_APROVADO,
                        'danger'  => Application::STATUS_REJEITADO,
                        'primary' => Application::STATUS_MATRICULADO,
                        'gray'    => Application::STATUS_CANCELADO,
                    ])
                    ->formatStateUsing(fn ($state) => [
                        Application::STATUS_EM_ANALISE => 'Em Análise',
                        Application::STATUS_APROVADO   => 'Aprovado',
                        Application::STATUS_REJEITADO  => 'Rejeitado',
                        Application::STATUS_MATRICULADO => 'Matriculado',
                        Application::STATUS_CANCELADO  => 'Cancelado',
                    ][$state] ?? $state),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrito em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('analisado_em')
                    ->label('Analisado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Application::STATUS_EM_ANALISE => 'Em Análise',
                        Application::STATUS_APROVADO   => 'Aprovado',
                        Application::STATUS_REJEITADO  => 'Rejeitado',
                        Application::STATUS_MATRICULADO => 'Matriculado',
                        Application::STATUS_CANCELADO  => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'titulo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Visualizar'),

                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->visible(fn ($record) => ! $record->isMatriculado()),

                Tables\Actions\Action::make('aprovar_e_matricular')
                    ->label('Aprovar e matricular')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar e matricular candidato')
                    ->modalDescription('Esta ação cria (ou reutiliza) a conta do aluno, gera a matrícula no curso e envia o link seguro para definição de senha.')
                    ->modalSubmitActionLabel('Confirmar aprovação')
                    ->form([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações internas (opcional)')
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => self::usuarioPodeGerenciar()
                        && in_array($record->status, [
                            Application::STATUS_EM_ANALISE,
                            Application::STATUS_APROVADO,
                        ], true))
                    ->action(function ($record, array $data): void {
                        /** @var AprovarInscricaoService $service */
                        $service = app(AprovarInscricaoService::class);

                        try {
                            $resultado = $service->executar(
                                $record,
                                auth()->user(),
                                $data['observacoes'] ?? null,
                            );

                            Notification::make()
                                ->title('Matrícula realizada com sucesso')
                                ->body(sprintf(
                                    'Aluno %s matriculado no curso %s. Matrícula nº %s.',
                                    $resultado->user->name,
                                    optional($resultado->matricula->curso)->titulo ?? '—',
                                    $resultado->matricula->numero_matricula,
                                ))
                                ->success()
                                ->send();
                        } catch (AprovarInscricaoException $e) {
                            Notification::make()
                                ->title('Não foi possível concluir a matrícula')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('rejeitar')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeitar inscrição')
                    ->modalDescription('Informe o motivo da rejeição. O candidato poderá ser notificado.')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo da rejeição')
                            ->required()
                            ->minLength(3)
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => self::usuarioPodeGerenciar()
                        && in_array($record->status, [
                            Application::STATUS_EM_ANALISE,
                            Application::STATUS_APROVADO,
                        ], true))
                    ->action(function ($record, array $data): void {
                        /** @var AprovarInscricaoService $service */
                        $service = app(AprovarInscricaoService::class);

                        try {
                            $service->rejeitar($record, auth()->user(), (string) ($data['motivo'] ?? ''));

                            Notification::make()
                                ->title('Inscrição rejeitada')
                                ->success()
                                ->send();
                        } catch (AprovarInscricaoException $e) {
                            Notification::make()
                                ->title('Não foi possível rejeitar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/nova'),
            'view'   => Pages\ViewApplication::route('/{record}'),
            'edit'   => Pages\EditApplication::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('deleted_at');
    }

    /**
     * Acesso ao recurso segue a coluna `users.nivel`
     * (admin, secretario, secretaria).
     */
    public static function canAccess(): bool
    {
        return self::usuarioPodeGerenciar();
    }
}
