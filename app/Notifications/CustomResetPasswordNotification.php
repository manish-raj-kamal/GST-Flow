<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset Password — '.config('app.name'))
            ->view('emails.password-reset-link', [
                'name' => $notifiable->name ?? 'User',
                'url' => $url,
                'expireMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                'appName' => config('app.name'),
            ]);
    }
}

