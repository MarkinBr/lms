<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class SecretariaLogin extends BaseLogin
{
    protected static string $view = 'filament.secretaria.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';
}
