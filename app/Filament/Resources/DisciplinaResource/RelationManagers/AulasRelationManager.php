<?php

namespace App\Filament\Resources\DisciplinaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * CRUD real de aulas dentro da disciplina (Issue #57).
 *
 * O relacionamento preenche disciplina_id automaticamente; o hook do model
 * Aula deriva modulo_id no servidor e rejeita cruzamento entre cursos.
 * Aulas legadas sem disciplina não aparecem aqui nem são remapeadas.
 */
class AulasRelationManager extends RelationManager
{
    protected static string $relationship = 'aulas';

    protected static ?string $title = 'Aulas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações básicas')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição curta')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('tipo_conteudo')
                            ->label('Tipo de conteúdo')
                            ->options([
                                'video' => 'Apenas vídeo',
                                'texto' => 'Apenas texto',
                                'misto' => 'Texto com vídeo',
                                'quiz' => 'Quiz/avaliação',
                            ])
                            ->required()
                            ->default('video')
                            ->live(),

                        Forms\Components\TextInput::make('duracao')
                            ->label('Duração')
                            ->placeholder('Ex: 15:30 ou 1:15:30'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Conteúdo')
                    ->schema([
                        Forms\Components\TextInput::make('video_url')
                            ->label('URL do vídeo')
                            ->url()
                            ->visible(fn (Forms\Get $get) => in_array($get('tipo_conteudo'), ['video', 'misto']))
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('conteudo_texto')
                            ->label('Conteúdo da aula')
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->helperText('Anexos devem ser publicados pelo fluxo privado de documentos; este editor não cria arquivos públicos.')
                            ->visible(fn (Forms\Get $get) => in_array($get('tipo_conteudo'), ['texto', 'misto']))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Liberação')
                    ->schema([
                        Forms\Components\TextInput::make('ordem')
                            ->label('Ordem')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->minValue(1),

                        Forms\Components\Toggle::make('liberada')
                            ->label('Aula liberada')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('titulo')
            ->reorderable('ordem')
            ->defaultSort('ordem')
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_conteudo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'video' => 'Vídeo',
                        'texto' => 'Texto',
                        'misto' => 'Misto',
                        'quiz' => 'Quiz',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duracao')
                    ->label('Duração')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('liberada')
                    ->label('Liberada')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (): bool => filled($this->getOwnerRecord()->modulo_id)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
