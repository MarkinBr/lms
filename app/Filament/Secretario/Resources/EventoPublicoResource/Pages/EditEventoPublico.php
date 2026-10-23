<?php
namespace App\Filament\Secretario\Resources\EventoPublicoResource\Pages;
use App\Filament\Secretario\Resources\EventoPublicoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditEventoPublico extends EditRecord {
 protected static string $resource=EventoPublicoResource::class;
 protected function getHeaderActions(): array {return [Actions\DeleteAction::make()];}
}
