<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CareerApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('New Career Application Submitted')
            ->greeting('Hello Tony,')
            ->line('A new career application has been submitted on WeWinGames.com.')
            ->line('Details:')
            ->line('Name: '.$this->data['first_name'].' '.$this->data['last_name'])
            ->line('Phone: '.$this->data['phone'])
            ->line('Email: '.$this->data['email'])
            ->line('About: '.$this->data['about'])
            ->line('Position: '.$this->data['position'])
            ->line('---')
            ->line('This is an automated notification.');
    }
}
