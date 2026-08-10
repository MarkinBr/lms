<?php

namespace App\Filament\Professor\Resources;

use App\Filament\Professor\Resources\CursoResource\Pages;
use App\Filament\Professor\Resources\CursoResource\RelationManagers;
use App\Models\Curso;
use App\Models\Modulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;

class CursoResource extends Resource
{
    protected static ?string $model = Curso::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Meus Cursos';
    protected static ?string $modelLabel = 'Curso';
    protected static ?string $pluralModelLabel = 'Meus Cursos';
    protected static ?string $navigationGroup = 'Gerenciamento de Conteúdo';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Informações do Curso')
                    ->tabs([
                        // ═══════════════════════════════════════
                        // TAB 1: INFORMAÇÕES BÁSICAS
                        // ═══════════════════════════════════════
                        Tabs\Tab::make('Informações Básicas')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Dados do Curso')
                                    ->description('Preencha as informações principais do curso')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título do Curso')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull()
                                            ->placeholder('Ex: Direito Administrativo para PC-RJ')
                                            ->autofocus(),

                                        Forms\Components\RichEditor::make('descricao')
                                            ->label('Descrição Completa')
                                            ->helperText('Descreva o curso, público-alvo, pré-requisitos e o que o aluno vai aprender')
                                            ->columnSpanFull()
                                            ->toolbarButtons([
                                                'bold', 'italic', 'underline', 'strike',
                                                'bulletList', 'orderedList',
                                                'h2', 'h3', 'link', 'undo', 'redo',
                                            ]),

                                        Forms\Components\FileUpload::make('capa')
                                            ->label('Imagem de Capa')
                                            ->image()
                                            ->directory('cursos/capas')
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['16:9', '4:3'])
                                            ->maxSize(5120)
                                            ->helperText('Tamanho recomendado: 1280x720px (16:9)')
                                            ->columnSpan(1),

                                        Forms\Components\FileUpload::make('thumbnail')
                                            ->label('Thumbnail / Miniatura')
                                            ->image()
                                            ->directory('cursos/thumbnails')
                                            ->imageEditor()
                                            ->maxSize(2048)
                                            ->helperText('Miniatura para listagens (400x300px)')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),

                        // ═══════════════════════════════════════
                        // TAB 2: CONFIGURAÇÕES
                        // ═══════════════════════════════════════
                        Tabs\Tab::make('Configurações')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Classificação')
                                    ->schema([
                                        Forms\Components\TextInput::make('categoria')
                                            ->label('Categoria / Área')
                                            ->placeholder('Ex: Direito, Teologia, Concursos')
                                            ->datalist([
                                                'Teologia', 'Direito', 'Liderança',
                                                'Música', 'Missões', 'Educação',
                                                'Concursos', 'Aconselhamento',
                                            ]),

                                        Forms\Components\Select::make('nivel')
                                            ->label('Nível de Dificuldade')
                                            ->options([
                                                'Básico' => '🟢 Básico',
                                                'Intermediário' => '🟡 Intermediário',
                                                'Avançado' => '🔴 Avançado',
                                            ])
                                            ->default('Básico')
                                            ->required(),

                                        Forms\Components\TextInput::make('carga_horaria')
                                            ->label('Carga Horária (horas)')
                                            ->numeric()
                                            ->suffix('horas')
                                            ->placeholder('Ex: 40'),

                                        Forms\Components\TextInput::make('ordem_exibicao')
                                            ->label('Ordem de Exibição')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Menor número = aparece primeiro'),
                                    ])
                                    ->columns(2),

                                Section::make('Valores e Publicação')
                                    ->schema([
                                        Forms\Components\TextInput::make('valor')
                                            ->label('Valor do Curso')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->default(0)
                                            ->step(0.01),

                                        Forms\Components\Toggle::make('ativo')
                                            ->label('Curso Publicado')
                                            ->helperText('Apenas cursos publicados aparecem para os alunos')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\Toggle::make('certificado_habilitado')
                                            ->label('Emitir Certificado')
                                            ->helperText('Habilita certificado automático ao concluir o curso')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\TextInput::make('video_preview')
                                            ->label('Vídeo de Apresentação (URL)')
                                            ->url()
                                            ->placeholder('https://www.youtube.com/watch?v=...')
                                            ->helperText('Vídeo gratuito para atrair alunos')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        // ═══════════════════════════════════════
                        // TAB 3: ESTATÍSTICAS (apenas edição)
                        // ═══════════════════════════════════════
                        Tabs\Tab::make('Estatísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->visible(fn ($record) => $record !== null)
                            ->schema([
                                Section::make('Números do Curso')
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_modulos')
                                            ->label('📦 Total de Módulos')
                                            ->content(fn ($record) => $record->modulos()->count()),

                                        Forms\Components\Placeholder::make('total_aulas')
                                            ->label('🎬 Total de Aulas')
                                            ->content(fn ($record) => $record->aulas()->count()),

                                        Forms\Components\Placeholder::make('total_matriculas')
                                            ->label('📋 Total de Matrículas')
                                            ->content(fn ($record) => $record->matriculas()->count()),

                                        Forms\Components\Placeholder::make('total_alunos_ativos')
                                            ->label('👥 Alunos Ativos')
                                            ->content(fn ($record) => $record->matriculas()->where('status', 'ativo')->count()),

                                        Forms\Components\Placeholder::make('duracao_calc')
                                            ->label('⏱️ Duração Total Calculada')
                                            ->content(function ($record) {
                                                $minutos = $record->aulas()->get()->sum(function($aula) {
                                                    $parts = explode(':', $aula->duracao ?? '0:00');
                                                    return count($parts) === 3 
                                                        ? ($parts[0] * 60 + $parts[1]) 
                                                        : ($parts[0] ?? 0);
                                                });
                                                $horas = intdiv($minutos, 60);
                                                $min = $minutos % 60;
                                                return "{$horas}h {$min}min";
                                            }),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('📅 Criado em')
                                            ->content(fn ($record) => $record->created_at?->format('d/m/Y H:i')),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Hidden field para instrutor_id
                Forms\Components\Hidden::make('instrutor_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('capa')
                    ->label('')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl(url('/images/default-course.png')),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->categoria . ' • ' . $record->nivel)
                    ->wrap(),

                Tables\Columns\TextColumn::make('modulos_count')
                    ->counts('modulos')
                    ->label('Módulos')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('aulas_count')
                    ->counts('aulas')
                    ->label('Aulas')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('matriculas_count')
                    ->counts('matriculas')
                    ->label('Alunos')
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Edição')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nivel')
                    ->label('Nível')
                    ->options([
                        'Básico' => 'Básico',
                        'Intermediário' => 'Intermediário',
                        'Avançado' => 'Avançado',
                    ]),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Publicado')
                    ->placeholder('Todos')
                    ->trueLabel('Publicados')
                    ->falseLabel('Rascunhos'),
            ])
            ->actions([
                Tables\Actions\Action::make('gerenciar_conteudo')
                    ->label('Conteúdo')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->url(fn ($record) => CursoResource::getUrl('edit', ['record' => $record])),

                Tables\Actions\EditAction::make()
                    ->label('Editar'),

                Tables\Actions\Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar Curso')
                    ->modalDescription('Isso criará uma cópia do curso com todos os módulos e aulas. Os alunos matriculados NÃO serão copiados.')
                    ->action(function (Curso $record) {
                        $novoCurso = $record->replicate();
                        $novoCurso->titulo = $record->titulo . ' (Cópia)';
                        $novoCurso->ativo = false;
                        $novoCurso->save();

                        foreach ($record->modulos as $modulo) {
                            $novoModulo = $modulo->replicate();
                            $novoModulo->curso_id = $novoCurso->id;
                            $novoModulo->save();

                            foreach ($modulo->aulas as $aula) {
                                $novaAula = $aula->replicate();
                                $novaAula->modulo_id = $novoModulo->id;
                                $novaAula->save();
                            }
                        }

                        Notification::make()
                            ->title('Curso duplicado com sucesso!')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Nenhum curso criado')
            ->emptyStateDescription('Crie seu primeiro curso clicando no botão acima')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCursos::route('/'),
            'create' => Pages\CreateCurso::route('/create'),
            'edit' => Pages\EditCurso::route('/{record}/edit'),
        ];
    }

    // Filtra apenas cursos do professor logado
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('instrutor_id', auth()->id())
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}
