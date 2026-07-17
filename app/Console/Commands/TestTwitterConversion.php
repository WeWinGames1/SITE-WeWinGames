<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwitterConversionService;
use Illuminate\Console\Command;

class TestTwitterConversion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:test-conversion
        {--user= : User id or email to attach as the conversion identifier}
        {--value=1.00 : Purchase value to report}
        {--dry-run : Build and print the payload without sending}
        {--force : Skip the confirmation prompt when sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send or preview a test/replay X (Twitter) purchase Conversion API event for a user';

    public function handle(TwitterConversionService $twitter): int
    {
        if (! $twitter->isConfigured()) {
            $this->error('X Conversion API is not configured. Set TWITTER_PIXEL_ID and TWITTER_CONVERSION_TOKEN.');

            return self::FAILURE;
        }

        $userRef = $this->option('user');

        if (! $userRef) {
            $this->error('Provide --user=<id|email> — a user is required for the match identifiers.');

            return self::FAILURE;
        }

        $user = is_numeric($userRef)
            ? User::find($userRef)
            : User::where('email', strtolower($userRef))->first();

        if (! $user) {
            $this->error("User not found: {$userRef}");

            return self::FAILURE;
        }

        $data = [
            'value' => (float) $this->option('value'),
            'currency' => 'USD',
            'conversion_id' => 'test-'.$user->id.'-'.now()->timestamp,
            'twclid' => $user->twclid,
            'event_source_url' => $user->landing_url ?: config('app.url'),
        ];

        $preview = $twitter->previewPurchase($user, $data);

        $this->info('Endpoint: '.$preview['endpoint']);
        $this->line('Payload (token is sent as a header, not shown):');
        $this->line((string) json_encode($preview['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($preview['payload'] === null) {
            $this->error('No matching identifier for this user (needs email, phone, or twclid). Aborting.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing was sent.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Send a LIVE test conversion for {$user->email} to X?", false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        if ($twitter->sendPurchase($user, $data)) {
            $this->info('Sent. Check X Events Manager and storage/logs/laravel.log.');

            return self::SUCCESS;
        }

        $this->error('Send failed — see storage/logs/laravel.log for the X CAPI error.');

        return self::FAILURE;
    }
}
