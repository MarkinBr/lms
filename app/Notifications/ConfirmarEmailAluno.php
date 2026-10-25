<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmarEmailAluno extends Notification
{
    public function __construct(public readonly string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme seu e-mail pessoal — Portal do Aluno')
            ->greeting('Olá!')
            ->line('Confirme este endereço para usá-lo no acesso e na recuperação de senha do Portal do Aluno.')
            ->action('Confirmar meu e-mail', $this->url)
            ->line('O link vale por 60 minutos. Entre com sua matrícula na mesma conta para confirmar.')
            ->line('Se você não solicitou esta confirmação, ignore esta mensagem.');
    }
}
