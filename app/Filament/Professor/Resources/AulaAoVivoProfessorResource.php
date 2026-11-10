<?php

namespace App\Filament\Professor\Resources;

use App\Filament\Professor\Resources\AulaAoVivoProfessorResource\Pages;
use App\Models\AulaAoVivo;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Issue #25 — visualização somente-leitura, para o professor, dos encontros
 * das turmas pelas quais ele é responsável. Reaproveita o painel "professor"
 * já existente (ver User::canAccessPanel); não é uma área nova/paralela.
 *
 * Sem create/edit/delete: o professor apenas consulta.
 */
class AulaAoVivoProfessorResource extends Resource
{
    protected static ?string $model = AulaAoVivo::class;

    protected static ?string $modelLabel = 'Encontro (Aula ao Vivo)';

    protected static ?string $pluralModelLabel = 'Minhas Aulas ao Vivo';

    protected static ?string $navigationLabel = 'Minhas Aulas ao Vivo';

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'Acadêmico';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) $user && $user->ativo && in_array($user->nivel, ['professor', 'admin'], true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        // Admin pode inspecionar este painel também, mas sem escopo de
        // "professor_id" (não é dono de nenhuma turma). Um professor só
        // enxerga os encontros das turmas pelas quais é responsável.
        if ($user && $user->nivel === 'professor') {
            $query->paraProfessor((int) $user->id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Encontro')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->descricao ? Str::limit($record->descricao, 50) : null),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->sortable(),

                Tables\Columns\TextColumn::make('modalidade')
                    ->label('Modalidade')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AulaAoVivo::MODALIDADE_ONLINE => 'info',
                        AulaAoVivo::MODALIDADE_PRESENCIAL => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => AulaAoVivo::MODALIDADE_LABELS[$state] ?? '—'),

                Tables\Columns\TextColumn::make('data_hora')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('local')
                    ->label('Local / Link')
                    ->formatStateUsing(fn ($record) => $record->modalidade === AulaAoVivo::MODALIDADE_PRESENCIAL
                        ? ($record->local ?: '—')
                        : ($record->link_transmissao ?: '—'))
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AulaAoVivo::STATUS_AGENDADA => 'success',
                        AulaAoVivo::STATUS_CANCELADA => 'danger',
                        AulaAoVivo::STATUS_REALIZADA => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => AulaAoVivo::STATUS_LABELS[$state] ?? AulaAoVivo::STATUS_LABELS[AulaAoVivo::STATUS_AGENDADA]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('turma_id')
                    ->label('Turma')
                    ->relationship('turma', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(AulaAoVivo::STATUS_LABELS),

                Tables\Filters\Filter::make('proximas')
                    ->label('Próximos encontros')
                    ->query(fn ($query) => $query->where('data_hora', '>=', now())),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('data_hora', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAulasAoVivoProfessor::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || $user->nivel !== 'professor') {
            return null;
        }

        $count = AulaAoVivo::query()
            ->paraProfessor((int) $user->id)
            ->where('data_hora', '>=', now())
            ->count();

        return $count > 0 ? (string) $count : null;
    }
}
