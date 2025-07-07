<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewBatchUploadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Collection $bets;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $bets)
    {
        $this->bets = $bets;
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
        $mail = (new MailMessage)
            ->subject('New Bet Picks Available')
            ->greeting('Hello!')
            ->line('New bet picks have been submitted. Here are the details:');

        foreach ($this->bets as $bet) {
            if ($notifiable->can('view', $bet)) {
                $mail->line('-----------------------------')
                    ->line('Sport: '.$bet->sports)
                    ->line('League: '.$bet->league)
                    ->line('Teams: '.$bet->team_one.' vs '.$bet->team_two)
                    ->line('Markets: '.$bet->markets)
                    ->line('Tips: '.$bet->tips)
                    ->line('Wager Odds: '.$bet->wager_odds)
                    ->line('Membership: '.$bet->membership)
                    ->line('Wager Amount: '.$bet->wager_amount)
                    ->line('Betting Date: '.$bet->betting_date);
            }
        }

        $mail->action('View Bets', url('/todays-tips'))
            ->line('Thank you for using our application!');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'bets' => collect($this->bets)->filter(function ($bet) use ($notifiable) {
                return method_exists($notifiable, 'can') && $notifiable->can('view', $bet);
            })->map(function ($bet) {
                return [
                    'sports' => $bet->sports,
                    'league' => $bet->league,
                    'team_one' => $bet->team_one,
                    'team_two' => $bet->team_two,
                    'markets' => $bet->markets,
                    'tips' => $bet->tips,
                    'wager_odds' => $bet->wager_odds,
                    'membership' => $bet->membership,
                    'wager_amount' => $bet->wager_amount,
                    'betting_date' => $bet->betting_date,
                ];
            })->values(),
            'message' => 'New bet picks submitted.',
        ];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $body = "New bet picks have been submitted:\n";
        foreach ($this->bets as $bet) {
            if (method_exists($notifiable, 'can') && $notifiable->can('view', $bet)) {
                $body .= "-----------------------------\n";
                $body .= "Sport: {$bet->sports}\n";
                $body .= "League: {$bet->league}\n";
                $body .= "Teams: {$bet->team_one} vs {$bet->team_two}\n";
                $body .= "Markets: {$bet->markets}\n";
                $body .= "Tips: {$bet->tips}\n";
                $body .= "Wager Odds: {$bet->wager_odds}\n";
                $body .= "Membership: {$bet->membership}\n";
                $body .= "Wager Amount: {$bet->wager_amount}\n";
                $body .= "Betting Date: {$bet->betting_date}\n";
            }
        }

        return (new WebPushMessage)
            ->title('New Bet Picks Submitted')
            ->icon('/images/icons/icon-192x192.png')
            ->body($body)
            ->action('View Bets', url('/dashboard'));
    }
}
