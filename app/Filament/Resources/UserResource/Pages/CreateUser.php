<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = UserResource::class;
}
