<?php

namespace App\Notifications;

use App\Mail\TemplatedEmail;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

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
        // Try to use the email template from database
        $template = EmailTemplate::where('key', EmailTemplate::CAREER_APPLICATION)
            ->where('is_active', true)
            ->first();

        if ($template) {
            // Prepare data for template variables
            $data = [
                'recipient_name' => 'Tony',
                'applicant_name' => $this->data['first_name'].' '.$this->data['last_name'],
                'applicant_phone' => $this->data['phone'],
                'applicant_email' => $this->data['email'],
                'applicant_about' => $this->data['about'],
                'applicant_position' => $this->data['position'],
                'app_name' => config('app.name', 'WeWinGames'),
                'submitted_date' => now()->format('M d, Y g:i A'),
            ];

            // Send using the templated email system
            Mail::to($notifiable)->send(new TemplatedEmail($template, $data));

            // Return a dummy MailMessage to satisfy the return type
            return (new \Illuminate\Notifications\Messages\MailMessage)->subject('Career Application');
        }

        // Fallback to default message if template not found
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
