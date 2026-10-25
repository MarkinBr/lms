<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AtivarAcessoAluno extends Notification
{
    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $minutos = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $url = URL::temporarySignedRoute('aluno.ativacao', now()->addMinutes($minutos), [
            'aluno' => $notifiable->id,
            'email' => $notifiable->email,
            'token' => $this->token,
        ]);

        return (new MailMessage)
            ->subject('Ative seu acesso — Portal do Aluno')
            ->greeting('Olá!')
            ->line('Sua matrícula de acesso permanente é '.$notifiable->matricula_login.'.')
            ->line('Defina sua senha pelo link individual abaixo. Seu e-mail será confirmado somente ao concluir esta ativação.')
            ->action('Ativar meu acesso', $url)
            ->line('Este link vale por '.$minutos.' minutos e só pode ser usado uma vez. Não o compartilhe.')
            ->line('Se precisar de outro link ou não reconhecer a matrícula, procure a secretaria.');
    }
}
