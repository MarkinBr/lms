<?php
namespace App\Filament\Secretario\Resources\EventoPublicoResource\Pages;
use App\Filament\Secretario\Resources\EventoPublicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListEventosPublicos extends ListRecords {
 protected static string $resource=EventoPublicoResource::class;
 protected function getHeaderActions(): array {return [Actions\CreateAction::make()->label('Novo evento')];}
}
