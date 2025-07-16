<?php

namespace App\Notifications;

use App\Mail\TemplatedEmail;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class SubscriptionCancelled extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Try to use the email template from database
        $template = EmailTemplate::where('key', EmailTemplate::SUBSCRIPTION_CANCELLED)
            ->where('is_active', true)
            ->first();

        if ($template) {
            // Prepare data for template variables
            $data = [
                'user_name' => $notifiable->name,
                'app_name' => config('app.name', 'WeWinGames'),
                'resubscribe_url' => url('/billing-portal'),
                'support_url' => url('/support'),
            ];

            // Send using the templated email system
            Mail::to($notifiable)->send(new TemplatedEmail($template, $data));
            
            // Return a dummy MailMessage to satisfy the return type
            return (new MailMessage)->subject('Subscription Cancelled');
        }

        // Fallback to default message if template not found
        return (new MailMessage)
            ->subject('Subscription Cancelled')
            ->line('Your subscription to We Win Games has been cancelled.')
            ->line('You will no longer have access to the game picks.')
            ->action('Resubscribe HERE', url('/billing-portal'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
