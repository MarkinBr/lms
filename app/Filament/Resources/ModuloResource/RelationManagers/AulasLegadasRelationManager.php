<?php

namespace App\Filament\Resources\ModuloResource\RelationManagers;

use App\Models\Disciplina;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Área transitória, somente para vincular aulas anteriores à Issue #57.
 * Não permite criar aulas diretamente no módulo.
 */
class AulasLegadasRelationManager extends RelationManager
{
    protected static string $relationship = 'aulas';

    protected static ?string $title = 'Aulas legadas sem disciplina';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('titulo_exibicao')
                ->label('Aula')
                ->content(fn ($record): string => $record?->titulo ?? 'Aula legada'),

            Forms\Components\Select::make('disciplina_id')
                ->label('Disciplina de destino')
                ->options(fn (): array => Disciplina::query()
                    ->where('modulo_id', $this->getOwnerRecord()->getKey())
                    ->where('curso_id', $this->getOwnerRecord()->curso_id)
                    ->where('ativa', true)
                    ->orderBy('ordem')
                    ->pluck('nome', 'id')
                    ->all())
                ->required()
                ->searchable()
                ->helperText('Somente disciplinas deste módulo podem receber a aula.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('disciplina_id'))
            ->recordTitleAttribute('titulo')
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Aula')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ordem')
                    ->label('Ordem'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Vincular à disciplina')
                    ->modalHeading('Vincular aula legada'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Nenhuma aula legada pendente')
            ->emptyStateDescription('Todas as aulas deste módulo já pertencem a uma disciplina.');
    }
}
