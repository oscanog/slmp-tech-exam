<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApiResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $token,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your SLMP API password')
            ->line('A password reset was requested for your account.')
            ->line('Submit the token below to POST '.rtrim(config('app.url'), '/').'/api/auth/reset-password.')
            ->line('Email: '.$notifiable->getEmailForPasswordReset())
            ->line('Token: '.$this->token)
            ->line('Include `email`, `token`, `password`, and `password_confirmation` in the JSON body.');
    }
}
