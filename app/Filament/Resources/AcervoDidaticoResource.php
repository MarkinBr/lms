<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcervoDidaticoResource\Pages;
use App\Models\AcervoDidatico;
use App\Models\Aula;
use App\Models\Disciplina;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AcervoDidaticoResource extends Resource
{
    protected static ?string $model = AcervoDidatico::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static ?string $navigationLabel = 'Acervo didático';

    protected static ?string $modelLabel = 'Material didático';

    protected static ?string $pluralModelLabel = 'Acervo didático';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vínculo acadêmico')
                ->description('O material só pode aparecer no curso selecionado. Não atribua uma disciplina ou aula sem conferir o conteúdo.')
                ->schema([
                    Forms\Components\Select::make('curso_id')
                        ->label('Curso')
                        ->relationship('curso', 'titulo')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (callable $set): void {
                            $set('disciplina_id', null);
                            $set('aula_id', null);
                        }),
                    Forms\Components\Select::make('disciplina_id')
                        ->label('Disciplina (quando identificada)')
                        ->options(fn (Forms\Get $get) => $get('curso_id')
                            ? Disciplina::query()->where('curso_id', $get('curso_id'))->orderBy('ordem')->pluck('nome', 'id')
                            : [])
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('aula_id', null)),
                    Forms\Components\Select::make('aula_id')
                        ->label('Aula (quando identificada)')
                        ->options(fn (Forms\Get $get) => $get('disciplina_id')
                            ? Aula::query()->where('disciplina_id', $get('disciplina_id'))->orderBy('ordem')->pluck('titulo', 'id')
                            : [])
                        ->searchable(),
                    Forms\Components\Select::make('classificacao')
                        ->label('Situação da classificação')
                        ->options([
                            'pendente' => 'Pendente — identificar curso',
                            'sugestao' => 'Associação temática sugerida — confirmar',
                            'curso' => 'Identificado no curso — falta aula exata',
                            'disciplina' => 'Identificado na disciplina',
                        ])
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Arquivo / link')
                ->schema([
                    Forms\Components\TextInput::make('titulo')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('tipo')
                        ->required()
                        ->options(['pdf' => 'PDF', 'docx' => 'Documento', 'video' => 'Vídeo', 'link' => 'Outro link']),
                    Forms\Components\TextInput::make('categoria')
                        ->label('Assunto / núcleo')
                        ->maxLength(180),
                    Forms\Components\TextInput::make('url')
                        ->label('Link HTTPS do material')
                        ->required()
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('publicado')
                        ->label('Publicar para alunos deste curso')
                        ->default(false)
                        ->helperText('Somente após conferir tema, permissão de acesso e autorização de uso.')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('curso.titulo')->label('Curso')->placeholder('Pendente')->wrap(),
                Tables\Columns\TextColumn::make('disciplina.nome')->label('Disciplina')->placeholder('Não identificada')->toggleable(),
                Tables\Columns\TextColumn::make('categoria')->label('Assunto')->toggleable(),
                Tables\Columns\TextColumn::make('tipo')->badge(),
                Tables\Columns\TextColumn::make('classificacao')->label('Vínculo')->badge(),
                Tables\Columns\IconColumn::make('publicado')->boolean()->label('Publicado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curso_id')->relationship('curso', 'titulo')->label('Curso'),
                Tables\Filters\TernaryFilter::make('publicado')->label('Publicado'),
                Tables\Filters\SelectFilter::make('classificacao')->options([
                    'pendente' => 'Pendente',
                    'sugestao' => 'Sugestão a validar',
                    'curso' => 'Somente curso',
                    'disciplina' => 'Disciplina',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->headerActions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcervoDidatico::route('/'),
            'create' => Pages\CreateAcervoDidatico::route('/create'),
            'edit' => Pages\EditAcervoDidatico::route('/{record}/edit'),
        ];
    }
}
