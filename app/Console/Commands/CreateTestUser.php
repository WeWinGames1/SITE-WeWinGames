<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test
                            {email : The email address for the test user}
                            {--name= : The name of the test user (optional)}
                            {--password= : The password for the test user (optional, will be generated if not provided)}
                            {--tier= : The subscription tier (free|gold|platinum)}
                            {--days= : Number of days until override expires (optional)}
                            {--ambassador : Mark as ambassador account}
                            {--gifted : Mark as gifted account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user account with optional tier override and expiration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        // Generate or use provided values
        $name = $this->option('name') ?? Str::before($email, '@');
        $password = $this->option('password') ?? Str::random(12);
        $tier = $this->option('tier');
        $days = $this->option('days');
        $isAmbassador = $this->option('ambassador');
        $isGifted = $this->option('gifted');

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_ambassador' => $isAmbassador ? 1 : 0,
            'is_gifted' => $isGifted ? 1 : 0,
            'status' => 'active',
        ]);

        // Apply tier override if specified
        if ($tier) {
            $validTiers = ['free', 'gold', 'platinum', 'bronze', 'silver']; // bronze/silver for legacy support
            if (!in_array(strtolower($tier), $validTiers)) {
                $this->error("Invalid tier. Must be one of: " . implode(', ', $validTiers));
                $user->delete();
                return 1;
            }

            $user->admin_override = 1;
            $user->override_tier = strtolower($tier);

            if ($days) {
                $user->override_expiry = now()->addDays((int) $days)->format('Y-m-d');
            }

            $user->save();
        }

        $this->info("✓ Test user created successfully!");
        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Name', $name],
                ['Password', $password],
                ['Tier Override', $tier ?? 'None'],
                ['Override Expiry', $user->override_expiry ?? 'Never'],
                ['Ambassador', $isAmbassador ? 'Yes' : 'No'],
                ['Gifted', $isGifted ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
        $this->warn("⚠ SAVE THIS PASSWORD: {$password}");
        $this->info("The user can log in at: " . config('app.url') . "/login");

        return 0;
    }
}
