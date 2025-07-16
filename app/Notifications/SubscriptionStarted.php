<?php

namespace App\Notifications;

use App\Mail\TemplatedEmail;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class SubscriptionStarted extends Notification implements ShouldQueue
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
        $template = EmailTemplate::where('key', EmailTemplate::WELCOME_SUBSCRIBER)
            ->where('is_active', true)
            ->first();

        if ($template) {
            // Prepare data for template variables
            $data = [
                'user_name' => $notifiable->name,
                'plan_name' => $notifiable->subscription?->stripe_price ?? 'Premium',
                'dashboard_url' => url('/dashboard'),
                'app_name' => config('app.name', 'WeWinGames'),
            ];

            // Send using the templated email system
            Mail::to($notifiable)->send(new TemplatedEmail($template, $data));
            
            // Return a dummy MailMessage to satisfy the return type
            return (new MailMessage)->subject('Subscription Started');
        }

        // Fallback to default message if template not found
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name', 'WeWinGames') . ' - Subscription Activated')
            ->line('You are now subscribed to We Win Games game picks.')
            ->line('You can now view the game picks in the dashboard.')
            ->action('View Now', url('/dashboard'))
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
