<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use App\Models\Curso;
use App\Models\CourseModality;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * ApplicationResource - Recurso de Inscrições no Filament
 * 
 * Gerencia inscrições de candidatos no painel da Secretaria
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
     * Definir o formulário de criação/edição
     */
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
                            ->maxLength(14) // CPF formatado: 000.000.000-00
                            ->mask('999.999.999-99')
                            ->unique(ignoreRecord: true),

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
                    ->description('Formas de contato')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
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
                    ->description('Endereço residencial completo')
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
                            ->maxLength(2)
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Dados Acadêmicos')
                    ->description('Informações sobre escolaridade e curso pretendido')
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
                            ->relationship('curso', 'nome')
                            ->preload()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Resetar modalidade quando curso mudar
                                $set('course_modality_id', null);
                            }),

                        Forms\Components\Select::make('course_modality_id')
                            ->label('Modalidade')
                            ->required()
                            ->options(function ($get) {
                                $cursoId = $get('curso_id');
                                if (!$cursoId) {
                                    return [];
                                }
                                
                                return CourseModality::where('curso_id', $cursoId)
                                    ->pluck('nome', 'id');
                            })
                            ->preload()
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Análise da Secretaria')
                    ->description('Avaliação da inscrição pela secretaria')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(1)
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                Application::STATUS_EM_ANALISE => 'Em Análise',
                                Application::STATUS_APROVADO => 'Aprovado',
                                Application::STATUS_REJEITADO => 'Rejeitado',
                                Application::STATUS_MATRICULADO => 'Matriculado',
                                Application::STATUS_CANCELADO => 'Cancelado',
                            ])
                            ->default(Application::STATUS_EM_ANALISE)
                            ->disabled(fn ($record) => $record?->isMatriculado()),

                        Forms\Components\Textarea::make('observacoes_secretaria')
                            ->label('Observações da Secretaria')
                            ->nullable()
                            ->rows(3)
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

    /**
     * Definir a tabela de listagem
     */
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

                Tables\Columns\TextColumn::make('curso.nome')
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
                        'danger' => Application::STATUS_REJEITADO,
                        'primary' => Application::STATUS_MATRICULADO,
                        'gray' => Application::STATUS_CANCELADO,
                    ])
                    ->formatStateUsing(fn ($state) => [
                        Application::STATUS_EM_ANALISE => 'Em Análise',
                        Application::STATUS_APROVADO => 'Aprovado',
                        Application::STATUS_REJEITADO => 'Rejeitado',
                        Application::STATUS_MATRICULADO => 'Matriculado',
                        Application::STATUS_CANCELADO => 'Cancelado',
                    ][$state]),

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
                    ->options([
                        Application::STATUS_EM_ANALISE => 'Em Análise',
                        Application::STATUS_APROVADO => 'Aprovado',
                        Application::STATUS_REJEITADO => 'Rejeitado',
                        Application::STATUS_MATRICULADO => 'Matriculado',
                        Application::STATUS_CANCELADO => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('curso')
                    ->relationship('curso', 'nome'),

                Tables\Filters\TernaryFilter::make('documentos_validados')
                    ->label('Documentos Validados')
                    ->placeholder('Todos')
                    ->trueLabel('Com documentos validados')
                    ->falseLabel('Sem documentos validados')
                    ->query(fn (Builder $query, bool $value): Builder => 
                        $query->whereHas('documents', fn ($q) => $q->where('validado', $value))
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Visualizar'),

                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->visible(fn ($record) => !$record->isMatriculado()),

                Tables\Actions\Action::make('approve')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar Inscrição')
                    ->modalDescription('Tem certeza que deseja aprovar esta inscrição? O candidato poderá ser matriculado.')
                    ->form([
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações (opcional)')
                            ->rows(3),
                    ])
                    ->action(function ($record, $data) {
                        $record->approve(auth()->id(), $data['observacoes'] ?? null);
                        
                        Notification::make()
                            ->title('Inscrição aprovada com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isEmAnalise()),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeitar Inscrição')
                    ->modalDescription('Informe o motivo da rejeição:')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo da Rejeição')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, $data) {
                        $record->reject(auth()->id(), $data['motivo']);
                        
                        Notification::make()
                            ->title('Inscrição rejeitada')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isEmAnalise()),

                Tables\Actions\Action::make('matricular')
                    ->label('Matricular')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Realizar Matrícula')
                    ->modalDescription('Esta ação criará a matrícula do aluno e enviará os dados de acesso.')
                    ->action(function ($record) {
                        // Aqui será implementada a lógica de matrícula
                        // Por enquanto, apenas marca como matriculado
                        
                        $record->markAsMatriculado();
                        
                        Notification::make()
                            ->title('Candidato marcado como matriculado')
                            ->success()
                            ->body('Agora é necessário criar a matrícula e usuário no sistema.')
                            ->send();
                    })
                    ->visible(fn ($record) => $record->isAprovado()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Definir páginas do recurso
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/nova'),
            'view' => Pages\ViewApplication::route('/{record}'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }

    /**
     * Escopo global: somente inscrições não deletadas
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('deleted_at');
    }

    /**
     * Verificar permissão para acessar este recurso
     */
    public static function canAccess(): bool
    {
        // Apenas Admin e Secretaria podem acessar
        return auth()->user()->hasAnyRole(['admin', 'secretaria']);
    }
}
