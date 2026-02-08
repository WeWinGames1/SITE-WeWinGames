<?php

namespace App\Console\Commands;

use App\Mail\TemplatedEmail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDiscordAnnouncementEmail extends Command
{
    protected $signature = 'discord:send-announcement
                            {--dry-run : Show who would receive emails without sending}
                            {--limit= : Limit number of emails to send}
                            {--test-email= : Send a test email to this address only}';

    protected $description = 'Send Discord community announcement to active subscribers who haven\'t connected Discord';

    public function handle(): int
    {
        // Get or create the email template
        $template = EmailTemplate::where('key', EmailTemplate::DISCORD_ANNOUNCEMENT)->first();

        if (! $template) {
            $this->warn('Discord announcement template not found. Creating it now...');

            $defaults = EmailTemplate::getDefaultTemplates()[EmailTemplate::DISCORD_ANNOUNCEMENT];
            $template = EmailTemplate::create([
                'key' => EmailTemplate::DISCORD_ANNOUNCEMENT,
                'name' => $defaults['name'],
                'description' => $defaults['description'],
                'subject' => $defaults['subject'],
                'body_html' => $defaults['body_html'],
                'available_variables' => $defaults['available_variables'],
                'is_active' => true,
            ]);

            $this->info('Template created. You can customize it in Admin > Email Templates.');
        }

        if (! $template->is_active) {
            $this->error('The Discord announcement template is disabled. Enable it in Admin > Email Templates.');

            return self::FAILURE;
        }

        // Handle test email
        if ($testEmail = $this->option('test-email')) {
            return $this->sendTestEmail($template, $testEmail);
        }

        // Get active subscribers who haven't connected Discord
        $query = User::whereNotNull('stripe_id')
            ->whereNull('discord_id')
            ->where(function ($q) {
                $q->whereHas('subscriptions', function ($sub) {
                    $sub->where('stripe_status', 'active');
                });
            });

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('No users found matching criteria (active subscribers without Discord connected).');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} subscribers without Discord connected.");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No emails will be sent.');
            $this->table(
                ['ID', 'Name', 'Email', 'Subscribed Since'],
                $users->map(fn ($u) => [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->created_at->format('Y-m-d'),
                ])->toArray()
            );

            return self::SUCCESS;
        }

        if (! $this->confirm("Send Discord announcement to {$users->count()} subscribers?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        $this->withProgressBar($users, function (User $user) use ($template, &$sent, &$failed) {
            try {
                $variables = [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'dashboard_url' => route('dashboard'),
                    'discord_invite_url' => config('services.discord.invite_url'),
                ];

                Mail::to($user->email)->send(new TemplatedEmail($template, $variables));
                $sent++;

                // Rate limit to avoid overwhelming mail server
                usleep(100000); // 0.1 second delay
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to send to {$user->email}: {$e->getMessage()}");
            }
        });

        $this->newLine(2);
        $this->info("Sent: {$sent} | Failed: {$failed}");

        return self::SUCCESS;
    }

    private function sendTestEmail(EmailTemplate $template, string $email): int
    {
        $this->info("Sending test email to: {$email}");

        try {
            $variables = [
                'user_name' => 'Test User',
                'user_email' => $email,
                'dashboard_url' => route('dashboard'),
                'discord_invite_url' => config('services.discord.invite_url'),
            ];

            Mail::to($email)->send(new TemplatedEmail($template, $variables));

            $this->info('Test email sent successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send test email: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
