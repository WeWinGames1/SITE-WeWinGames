<?php

namespace App\Notifications;

use App\Mail\TemplatedEmail;
use App\Models\Bet;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewBetPick extends Notification implements ShouldQueue
{
    use Queueable;

    private Bet $bet;

    /**
     * Create a new notification instance.
     */
    public function __construct(Bet $bet)
    {
        $this->bet = $bet;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Check if the user has enabled email notifications
        if ($notifiable->notification_preferences['email'] ?? false) {
            $channels[] = 'mail';
        }

        // Check if the user has enabled push notifications
        if ($notifiable->notification_preferences['push'] ?? false) {
            $channels[] = WebPushChannel::class;
        }

        // Always store the notification in the database
        $channels[] = 'database';

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Try to use the email template from database
        $template = EmailTemplate::where('key', EmailTemplate::NEW_BET_PICK)
            ->where('is_active', true)
            ->first();

        if ($template) {
            // Prepare data for template variables
            $data = [
                'user_name' => $notifiable->name,
                'sport' => $this->bet->sports,
                'league' => $this->bet->league,
                'team_one' => $this->bet->team_one,
                'team_two' => $this->bet->team_two,
                'markets' => $this->bet->markets,
                'tips' => $this->bet->tips,
                'wager_odds' => $this->bet->wager_odds,
                'membership' => $this->bet->membership,
                'wager_amount' => $this->bet->wager_amount,
                'betting_date' => $this->bet->betting_date,
                'bet_url' => url('/pick/'.$this->bet->id),
                'app_name' => config('app.name', 'WeWinGames'),
            ];

            // Send using the templated email system
            Mail::to($notifiable)->send(new TemplatedEmail($template, $data));

            // Return a dummy MailMessage to satisfy the return type
            return (new MailMessage)->subject('New Bet Pick');
        }

        // Fallback to default message if template not found
        return (new MailMessage)
            ->subject('New Bet Pick Submitted')
            ->greeting('Hello!')
            ->line('A new bet pick has been submitted. Here are the details:')
            ->line('Sport: '.$this->bet->sports)
            ->line('League: '.$this->bet->league)
            ->line('Teams: '.$this->bet->team_one.' vs '.$this->bet->team_two)
            ->line('Markets: '.$this->bet->markets)
            ->line('Tips: '.$this->bet->tips)
            ->line('Wager Odds: '.$this->bet->wager_odds)
            ->line('Membership: '.$this->bet->membership)
            ->line('Wager Amount: '.$this->bet->wager_amount)
            ->line('Betting Date: '.$this->bet->betting_date)
            ->action('View Bet', url('/pick/'.$this->bet->id))
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
            'sports' => $this->bet->sports,
            'league' => $this->bet->league,
            'team_one' => $this->bet->team_one,
            'team_two' => $this->bet->team_two,
            'markets' => $this->bet->markets,
            'tips' => $this->bet->tips,
            'wager_odds' => $this->bet->wager_odds,
            'membership' => $this->bet->membership,
            'wager_amount' => $this->bet->wager_amount,
            'betting_date' => $this->bet->betting_date,
            'message' => 'New bet pick submitted.',
        ];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('New Bet Pick Submitted')
            ->icon('/images/icons/icon-192x192.png')
            ->body(
                "Sport: {$this->bet->sports}\n".
                "League: {$this->bet->league}\n".
                "Teams: {$this->bet->team_one} vs {$this->bet->team_two}\n".
                "Markets: {$this->bet->markets}\n".
                "Tips: {$this->bet->tips}\n".
                "Wager Odds: {$this->bet->wager_odds}\n".
                "Membership: {$this->bet->membership}\n".
                "Wager Amount: {$this->bet->wager_amount}\n".
                "Betting Date: {$this->bet->betting_date}"
            )
            ->action('View Bet', url('/dashboard'));
    }
}
