<?php

namespace App\Filament\Resources\ModuloResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AulasRelationManager extends RelationManager
{
    protected static string $relationship = 'aulas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição Curta')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('tipo_conteudo')
                            ->label('Tipo de Conteúdo')
                            ->options([
                                'video' => 'Apenas Vídeo',
                                'texto' => 'Apenas Texto',
                                'misto' => 'Texto com Vídeo',
                                'quiz' => 'Quiz/Avaliação',
                            ])
                            ->required()
                            ->default('video')
                            ->reactive()
                            ->columnSpan(1),
                        
                        Forms\Components\TextInput::make('duracao')
                            ->label('Duração')
                            ->placeholder('Ex: 15:30 ou 1:15:30')
                            ->helperText('Formato: MM:SS ou HH:MM:SS')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Conteúdo da Aula')
                    ->schema([
                        // Campo de vídeo (aparece quando tipo_conteudo é 'video' ou 'misto')
                        Forms\Components\TextInput::make('video_url')
                            ->label('URL do Vídeo')
                            ->url()
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->helperText('Suporta YouTube, Vimeo ou vídeo direto')
                            ->visible(fn (Forms\Get $get) => in_array($get('tipo_conteudo'), ['video', 'misto']))
                            ->columnSpanFull(),
                        
                        // EDITOR RICO - Campo principal de conteúdo
                        Forms\Components\RichEditor::make('conteudo_texto')
                            ->label('Conteúdo da Matéria')
                            ->helperText('Escreva o conteúdo completo da aula. Você pode inserir imagens, vídeos, listas, tabelas, etc.')
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'h4',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('aulas/conteudo')
                            ->fileAttachmentsVisibility('public')
                            ->visible(fn (Forms\Get $get) => in_array($get('tipo_conteudo'), ['texto', 'misto']))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Materiais de Apoio')
                    ->schema([
                        Forms\Components\Repeater::make('materiais')
                            ->label('Materiais')
                            ->schema([
                                Forms\Components\TextInput::make('titulo')
                                    ->label('Título do Material')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo')
                                    ->options([
                                        'pdf' => 'PDF',
                                        'video' => 'Vídeo',
                                        'link' => 'Link',
                                        'arquivo' => 'Arquivo',
                                    ])
                                    ->required()
                                    ->columnSpan(1),
                                
                                Forms\Components\TextInput::make('url')
                                    ->label('URL do Material')
                                    ->url()
                                    ->required()
                                    ->columnSpan(3),
                            ])
                            ->columns(6)
                            ->collapsed()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['titulo'] ?? null)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configurações de Liberação')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('ordem')
                                    ->label('Ordem')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                
                                Forms\Components\Toggle::make('liberada')
                                    ->label('Aula Liberada')
                                    ->default(true)
                                    ->inline(false),
                                
                                Forms\Components\Select::make('tipo_liberacao')
                                    ->label('Tipo de Liberação')
                                    ->options([
                                        'imediata' => 'Imediata',
                                        'agendada' => 'Agendada',
                                        'sequencial' => 'Sequencial',
                                    ])
                                    ->default('imediata')
                                    ->reactive(),
                            ]),
                        
                        Forms\Components\DateTimePicker::make('data_liberacao')
                            ->label('Data de Liberação')
                            ->visible(fn (Forms\Get $get) => $get('tipo_liberacao') === 'agendada')
                            ->columnSpan(1),
                    ])
                    ->columns(1),
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
                    ->sortable()
                    ->description(fn ($record) => $record->descricao),
                
                Tables\Columns\BadgeColumn::make('tipo_conteudo')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'video',
                        'success' => 'texto',
                        'warning' => 'misto',
                        'info' => 'quiz',
                    ])
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
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_conteudo')
                    ->label('Tipo de Conteúdo')
                    ->options([
                        'video' => 'Vídeo',
                        'texto' => 'Texto',
                        'misto' => 'Misto',
                        'quiz' => 'Quiz',
                    ]),
                
                Tables\Filters\TernaryFilter::make('liberada')
                    ->label('Liberada')
                    ->placeholder('Todas')
                    ->trueLabel('Sim')
                    ->falseLabel('Não'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}