<?php

namespace App\Filament\Secretario\Resources\TurmaSecretariaResource\RelationManagers;

use App\Models\Aula;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use App\Models\ProfessorAtribuicao;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProfessoresTurmaRelationManager extends RelationManager
{
    protected static string $relationship = 'professorAtribuicoes';

    protected static ?string $title = 'Professores e responsabilidades';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('professor_id')
                ->label('Professor')
                ->options(User::query()->where('nivel', 'professor')->where('ativo', true)
                    ->orderBy('name')->pluck('name', 'id'))
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('escopo')
                ->label('Responsabilidade')
                ->options(ProfessorAtribuicao::ESCOPOS)
                ->live()->required()
                ->afterStateUpdated(function (Forms\Set $set) {
                    foreach (['disciplina_id', 'modulo_id', 'aula_id', 'instrumento_id'] as $campo) {
                        $set($campo, null);
                    }
                }),
            Forms\Components\Select::make('disciplina_id')
                ->label('Disciplina')
                ->options(fn () => $this->getOwnerRecord()->disciplinas()
                    ->wherePivot('ativa', true)->pluck('disciplinas.nome', 'disciplinas.id'))
                ->visible(fn (Forms\Get $get) => $get('escopo') !== 'turma')
                ->required(fn (Forms\Get $get) => $get('escopo') !== 'turma')
                ->live()->afterStateUpdated(function (Forms\Set $set) {
                    $set('modulo_id', null);
                    $set('aula_id', null);
                    $set('instrumento_id', null);
                }),
            Forms\Components\Select::make('modulo_id')
                ->label('Módulo')
                ->options(fn (Forms\Get $get) => $get('disciplina_id')
                    ? Disciplina::query()->find($get('disciplina_id'))?->modulos()
                        ->pluck('modulos.titulo', 'modulos.id') ?? []
                    : [])
                ->visible(fn (Forms\Get $get) => in_array($get('escopo'), ['modulo', 'aula'], true))
                ->required(fn (Forms\Get $get) => in_array($get('escopo'), ['modulo', 'aula'], true))
                ->live()->afterStateUpdated(fn (Forms\Set $set) => $set('aula_id', null)),
            Forms\Components\Select::make('aula_id')
                ->label('Aula')
                ->options(fn (Forms\Get $get) => Aula::query()
                    ->where('disciplina_id', $get('disciplina_id') ?: 0)
                    ->where('modulo_id', $get('modulo_id') ?: 0)->pluck('titulo', 'id'))
                ->visible(fn (Forms\Get $get) => $get('escopo') === 'aula')
                ->required(fn (Forms\Get $get) => $get('escopo') === 'aula'),
            Forms\Components\Select::make('instrumento_id')
                ->label('Prova, teste ou avaliação')
                ->options(fn (Forms\Get $get) => InstrumentoAvaliacao::query()
                    ->where('disciplina_id', $get('disciplina_id') ?: 0)->pluck('titulo', 'id'))
                ->visible(fn (Forms\Get $get) => $get('escopo') === 'instrumento')
                ->required(fn (Forms\Get $get) => $get('escopo') === 'instrumento'),
            Forms\Components\CheckboxList::make('permissoes')
                ->label('Permissões explicitamente concedidas')
                ->options(ProfessorAtribuicao::PERMISSOES)
                ->columns(2)->required(),
            Forms\Components\Toggle::make('ativa')->label('Atribuição ativa')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('professor.name')->label('Professor')->searchable(),
            Tables\Columns\TextColumn::make('escopo')->label('Escopo')->badge()
                ->formatStateUsing(fn ($state) => ProfessorAtribuicao::ESCOPOS[$state] ?? $state),
            Tables\Columns\TextColumn::make('disciplina.nome')->label('Disciplina')->placeholder('Todas'),
            Tables\Columns\TextColumn::make('modulo.titulo')->label('Módulo')->placeholder('Todos'),
            Tables\Columns\TextColumn::make('aula.titulo')->label('Aula')->placeholder('Todas'),
            Tables\Columns\TextColumn::make('instrumento.titulo')->label('Avaliação')->placeholder('Todas'),
            Tables\Columns\IconColumn::make('ativa')->boolean(),
        ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Designar professor')])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record): bool => ! ($record->escopo === 'turma'
                        && (int) $record->professor_id === (int) $this->getOwnerRecord()->professor_id)),
            ])
            ->bulkActions([]);
    }
}
