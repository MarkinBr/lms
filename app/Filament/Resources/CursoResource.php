<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CursoResource\Pages;
use App\Filament\Resources\CursoResource\RelationManagers;
use App\Models\Curso;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CursoResource extends Resource
{
    protected static ?string $model = Curso::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'Cursos';
    
    protected static ?string $modelLabel = 'Curso';
    
    protected static ?string $pluralModelLabel = 'Cursos';
    
    protected static ?string $navigationGroup = 'Acadêmico';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Informações do Curso')
                    ->tabs([
                        // Tab 1: Informações Básicas
                        Tabs\Tab::make('Informações Básicas')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título do Curso')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull()
                                            ->placeholder('Ex: Teologia Básica'),

                                        Forms\Components\RichEditor::make('descricao')
                                            ->label('Descrição Completa')
                                            ->columnSpanFull()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'strike',
                                                'bulletList',
                                                'orderedList',
                                                'h2',
                                                'h3',
                                                'link',
                                                'undo',
                                                'redo',
                                            ]),

                                        Forms\Components\FileUpload::make('capa')
                                            ->label('Imagem de Capa')
                                            ->image()
                                            ->directory('cursos/capas')
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                            ])
                                            ->maxSize(2048)
                                            ->helperText('Tamanho recomendado: 1280x720px')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 2: Configurações
                        Tabs\Tab::make('Configurações')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Classificação')
                                    ->schema([
                                        Forms\Components\TextInput::make('categoria')
                                            ->label('Categoria')
                                            ->placeholder('Ex: Teologia, Liderança, Música')
                                            ->datalist([
                                                'Teologia',
                                                'Liderança',
                                                'Música',
                                                'Missões',
                                                'Educação Cristã',
                                                'Família',
                                                'Aconselhamento',
                                            ]),

                                        Forms\Components\Select::make('nivel')
                                            ->label('Nível')
                                            ->options([
                                                'Básico' => 'Básico',
                                                'Intermediário' => 'Intermediário',
                                                'Avançado' => 'Avançado',
                                            ])
                                            ->default('Básico')
                                            ->required(),
                                    ])
                                    ->columns(2),

                                Section::make('Informações Financeiras e Temporais')
                                    ->schema([
                                        Forms\Components\TextInput::make('duracao_total')
                                            ->label('Duração Total (minutos)')
                                            ->numeric()
                                            ->suffix('min')
                                            ->helperText('Soma automática das aulas quando criadas'),

                                        Forms\Components\TextInput::make('valor')
                                            ->label('Valor do Curso')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->default(0)
                                            ->step(0.01),

                                        Forms\Components\Toggle::make('ativo')
                                            ->label('Curso Ativo')
                                            ->helperText('Apenas cursos ativos aparecem para matrícula')
                                            ->default(true)
                                            ->inline(false),
                                    ])
                                    ->columns(3),
                            ]),

                        // Tab 3: Estatísticas (apenas em edição)
                        Tabs\Tab::make('Estatísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->visible(fn ($record) => $record !== null)
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_modulos')
                                            ->label('Total de Módulos')
                                            ->content(fn ($record) => $record->modulos()->count()),

                                        Forms\Components\Placeholder::make('total_aulas')
                                            ->label('Total de Aulas')
                                            ->content(fn ($record) => $record->aulas()->count()),

                                        Forms\Components\Placeholder::make('total_matriculas')
                                            ->label('Total de Matrículas')
                                            ->content(fn ($record) => $record->matriculas()->count()),

                                        Forms\Components\Placeholder::make('total_alunos_ativos')
                                            ->label('Alunos Ativos')
                                            ->content(fn ($record) => $record->matriculas()->where('status', 'ativo')->count()),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Criado em')
                                            ->content(fn ($record) => $record->created_at?->format('d/m/Y H:i')),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Atualizado em')
                                            ->content(fn ($record) => $record->updated_at?->format('d/m/Y H:i')),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('capa')
                    ->label('Capa')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-course.png')),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->categoria),

                Tables\Columns\TextColumn::make('nivel')
                    ->label('Nível')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Básico' => 'success',
                        'Intermediário' => 'warning',
                        'Avançado' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('modulos_count')
                    ->counts('modulos')
                    ->label('Módulos')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('aulas_count')
                    ->counts('aulas')
                    ->label('Aulas')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('matriculas_count')
                    ->counts('matriculas')
                    ->label('Matrículas')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nivel')
                    ->label('Nível')
                    ->options([
                        'Básico' => 'Básico',
                        'Intermediário' => 'Intermediário',
                        'Avançado' => 'Avançado',
                    ]),

                Tables\Filters\SelectFilter::make('categoria')
    ->label('Categoria')
    ->options(fn () => Curso::distinct()
        ->whereNotNull('categoria') // <--- ADICIONE ESTA LINHA
        ->where('categoria', '!=', '') // Opcional: para evitar strings vazias
        ->pluck('categoria', 'categoria')
        ->toArray()),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações do Curso')
                    ->schema([
                        Infolists\Components\ImageEntry::make('capa')
                            ->label('Capa')
                            ->defaultImageUrl(url('/images/default-course.png'))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('titulo')
                            ->label('Título'),

                        Infolists\Components\TextEntry::make('categoria')
                            ->label('Categoria'),

                        Infolists\Components\TextEntry::make('nivel')
                            ->label('Nível')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Básico' => 'success',
                                'Intermediário' => 'warning',
                                'Avançado' => 'danger',
                            }),

                        Infolists\Components\TextEntry::make('valor')
                            ->label('Valor')
                            ->money('BRL'),

                        Infolists\Components\TextEntry::make('duracao_total')
                            ->label('Duração Total')
                            ->suffix(' minutos'),

                        Infolists\Components\IconEntry::make('ativo')
                            ->label('Ativo')
                            ->boolean(),

                        Infolists\Components\TextEntry::make('descricao')
                            ->label('Descrição')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Estatísticas')
                    ->schema([
                        Infolists\Components\TextEntry::make('modulos_count')
                            ->label('Total de Módulos')
                            ->state(fn ($record) => $record->modulos()->count()),

                        Infolists\Components\TextEntry::make('aulas_count')
                            ->label('Total de Aulas')
                            ->state(fn ($record) => $record->aulas()->count()),

                        Infolists\Components\TextEntry::make('matriculas_count')
                            ->label('Total de Matrículas')
                            ->state(fn ($record) => $record->matriculas()->count()),

                        Infolists\Components\TextEntry::make('alunos_ativos')
                            ->label('Alunos Ativos')
                            ->state(fn ($record) => $record->matriculas()->where('status', 'ativo')->count()),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Informações de Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulosRelationManager::class,
            RelationManagers\MatriculasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCursos::route('/'),
            'create' => Pages\CreateCurso::route('/create'),
            'view' => Pages\ViewCurso::route('/{record}'),
            'edit' => Pages\EditCurso::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
