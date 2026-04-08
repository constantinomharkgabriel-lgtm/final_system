<?php

namespace App\Notifications;

use App\Models\Driver;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyDriverEmail extends Notification
{

    public function __construct(private Driver $driver)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl();

        return (new MailMessage)
            ->subject('🚗 Welcome to Driver Portal - Verify Your Email')
            ->greeting('Welcome, ' . $this->driver->name . '!')
            ->line('You have been registered as a delivery driver for our logistics system.')
            ->line('To access your Driver Portal and start accepting deliveries, please verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('This link will expire in 60 minutes.')
            ->line('')
            ->line('**Driver Portal Features:**')
            ->line('• View assigned deliveries in real-time')
            ->line('• Accept or reject delivery tasks')
            ->line('• Track earnings and payments')
            ->line('• Upload delivery proofs')
            ->line('• View performance ratings')
            ->line('')
            ->line('Once verified, your profile will appear to logistics staff for delivery assignments.')
            ->line('')
            ->line('Questions? Contact our support team.')
            ->salutation('Best regards,')
            ->markdown('notifications.driver.email-verify');
    }

    protected function verificationUrl()
    {
        return URL::temporarySignedRoute(
            'driver.email.verify',
            now()->addMinutes(60),
            [
                'driver' => $this->driver->id,
                'hash' => sha1($this->driver->email),
            ]
        );
    }
}
