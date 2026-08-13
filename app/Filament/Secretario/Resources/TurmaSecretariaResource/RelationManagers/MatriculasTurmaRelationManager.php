<?php

namespace App\Filament\Secretario\Resources\TurmaSecretariaResource\RelationManagers;

use App\Models\MatriculaTurma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MatriculasTurmaRelationManager extends RelationManager
{
    protected static string $relationship = 'matriculasTurmas';

    protected static ?string $title = 'Matrículas vinculadas';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_matricula')
                    ->label('Matrícula')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status_vinculo')
                    ->label('Status')
                    ->state(fn ($record) => $record->pivot->status ?? null)
                    ->badge()
                    ->colors([
                        'success' => MatriculaTurma::STATUS_ATIVO,
                        'warning' => MatriculaTurma::STATUS_TRANCADO,
                        'info' => MatriculaTurma::STATUS_CONCLUIDO,
                        'danger' => MatriculaTurma::STATUS_CANCELADO,
                    ])
                    ->formatStateUsing(fn (?string $state): string => MatriculaTurma::STATUS_LABELS[$state] ?? $state ?? '—'),

                Tables\Columns\TextColumn::make('vinculado_em')
                    ->label('Vinculado em')
                    ->state(fn ($record) => $record->pivot->created_at)
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                Tables\Filters\Filter::make('status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(MatriculaTurma::STATUS_LABELS)
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['status'] ?? null,
                            fn (Builder $q, string $status) => $q->wherePivot('status', $status)
                        );
                    }),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
