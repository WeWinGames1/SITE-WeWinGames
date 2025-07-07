<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AssignRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-role {user} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a role to a user in the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $user = $this->argument('user');
        $role = $this->argument('role');
        $userModel = \App\Models\User::where('email', $user)->first();
        if (! $userModel) {
            $this->error("User '$user' does not exist.");

            return;
        }
        if ($userModel->hasRole($role)) {
            $this->error("User '$user' already has the role '$role'.");

            return;
        }
        $userModel->assignRole($role);
        $this->info("Role '$role' assigned to user '$user' successfully.");
        // Optionally, you can also display the user's roles after assignment
        $roles = $userModel->getRoleNames();
        $this->info("User '$user' now has the following roles: ".implode(', ', $roles->toArray()));
        // Optionally, you can also display the user's permissions after assignment
        $permissions = $userModel->getAllPermissions();
        $this->info("User '$user' has the following permissions: ".implode(', ', $permissions->pluck('name')->toArray()));
    }
}
