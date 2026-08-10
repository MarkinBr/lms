<?php

namespace App\Filament\Professor\Resources\CursoResource\RelationManagers;

use App\Filament\Professor\Resources\AulaResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ModulosRelationManager extends RelationManager
{
    protected static string $relationship = 'modulos';
    protected static ?string $title = 'Módulos e Aulas';
    protected static ?string $modelLabel = 'Módulo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Módulo')
                    ->icon('heroicon-o-folder')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título do Módulo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ex: Módulo 1 - Princípios Administrativos'),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Descrição breve do conteúdo deste módulo...'),

                        Forms\Components\TextInput::make('ordem')
                            ->label('Ordem de Exibição')
                            ->numeric()
                            ->default(fn () => $this->ownerRecord->modulos()->count() + 1)
                            ->required()
                            ->minValue(1),
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
                    ->alignCenter()
                    ->width(50)
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Módulo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => \Illuminate\Support\Str::limit($record->descricao, 80)),

                Tables\Columns\TextColumn::make('aulas_count')
                    ->counts('aulas')
                    ->label('Aulas')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->suffix(' aulas'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Novo Módulo')
                    ->icon('heroicon-o-folder-plus')
                    ->modalHeading('Criar Novo Módulo'),
            ])
            ->actions([
                // Botão principal: Gerenciar Aulas deste módulo
                Tables\Actions\Action::make('gerenciar_aulas')
                    ->label('Gerenciar Aulas')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->url(fn ($record) => AulaResource::getUrl('index', ['modulo_id' => $record->id])),

                Tables\Actions\Action::make('adicionar_aula_rapida')
                    ->label('+ Aula Rápida')
                    ->icon('heroicon-o-plus')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título da Aula')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Aula 01 - Introdução'),

                        Forms\Components\Select::make('tipo_conteudo')
                            ->label('Tipo de Conteúdo')
                            ->options([
                                'video' => '🎬 Vídeo Aula',
                                'texto' => '📝 Texto/Artigo',
                                'misto' => '📦 Misto (Vídeo + Texto)',
                                'quiz' => '❓ Quiz/Avaliação',
                            ])
                            ->default('misto')
                            ->required(),

                        Forms\Components\TextInput::make('video_url')
                            ->label('URL do Vídeo (opcional)')
                            ->url()
                            ->placeholder('https://www.youtube.com/watch?v=...'),

                        Forms\Components\TextInput::make('duracao')
                            ->label('Duração')
                            ->placeholder('Ex: 15:30'),
                    ])
                    ->action(function (array $data, $record) {
                        $ordem = $record->aulas()->count() + 1;
                        $record->aulas()->create([
                            'titulo' => $data['titulo'],
                            'tipo_conteudo' => $data['tipo_conteudo'],
                            'video_url' => $data['video_url'] ?? null,
                            'duracao' => $data['duracao'] ?? null,
                            'ordem' => $ordem,
                            'liberada' => true,
                            'tipo_liberacao' => 'imediata',
                        ]);

                        Notification::make()
                            ->title('Aula criada com sucesso!')
                            ->body("A aula '{$data['titulo']}' foi adicionada ao módulo.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->modalHeading('Editar Módulo'),

                Tables\Actions\DeleteAction::make()
                    ->label('Excluir')
                    ->modalHeading('Excluir Módulo')
                    ->modalDescription('Isso excluirá o módulo e TODAS as suas aulas. Esta ação não pode ser desfeita.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum módulo criado')
            ->emptyStateDescription('Adicione o primeiro módulo ao seu curso')
            ->emptyStateIcon('heroicon-o-folder-open');
    }
}
