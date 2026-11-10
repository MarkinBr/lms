<?php

namespace App\Filament\Resources\AulaAoVivoResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\AulaAoVivoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAulaAoVivo extends CreateRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = AulaAoVivoResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Aula ao vivo criada com sucesso!';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
