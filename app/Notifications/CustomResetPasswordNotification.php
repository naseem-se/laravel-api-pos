<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPasswordBase
{

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_public_url'), '/');
        $url = "{$frontendUrl}/reset-password/{$this->token}?email=".urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset your password')
            ->line('You requested a password reset.')
            ->action('Reset Password', $url)
            ->line('This link expires in 60 minutes. If you didn\'t request this, you can ignore this email.');
    }
}