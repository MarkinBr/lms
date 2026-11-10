<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * Login administrativo com a identidade visual institucional.
 *
 * A autenticação continua sendo a do Filament: Livewire, CSRF, rate limit,
 * sessão regenerada e canAccessPanel() do User. A tela nunca autentica no
 * navegador e não contém credenciais de demonstração.
 */
class AdminLogin extends BaseLogin
{
    protected static string $view = 'filament.admin.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('identifier')
            ->label('Usuário ou e-mail')
            ->placeholder('Digite seu usuário ou e-mail')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Senha')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $identifier = trim((string) ($data['identifier'] ?? ''));

        return [
            filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username' => filter_var($identifier, FILTER_VALIDATE_EMAIL)
                    ? mb_strtolower($identifier)
                    : $identifier,
            'password' => (string) ($data['password'] ?? ''),
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.identifier' => 'As credenciais informadas não são válidas.',
        ]);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Entrar no painel')
            ->submit('authenticate');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    public function getHeading(): string|HtmlString
    {
        return new HtmlString('Acesso <em class="admin-login-heading-accent">administrativo</em>');
    }

    public function getTitle(): string|HtmlString
    {
        return 'Acesso administrativo';
    }
}
