<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset;

class AdminRequestPasswordReset extends RequestPasswordReset
{
    protected static string $view = 'filament.admin.auth.password-reset.request-password-reset';
}
