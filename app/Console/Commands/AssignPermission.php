<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AssignPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-permission {user} {permission}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a permission to a user in the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $user = $this->argument('user');
        $permission = $this->argument('permission');
        $userModel = \App\Models\User::where('email', $user)->first();
        if (! $userModel) {
            $this->error("User '$user' does not exist.");

            return;
        }
        if ($userModel->hasPermissionTo($permission)) {
            $this->error("User '$user' already has the permission '$permission'.");

            return;
        }
        $userModel->givePermissionTo($permission);
        $this->info("Permission '$permission' assigned to user '$user' successfully.");
        // Optionally, you can also display the user's permissions after assignment
        $permissions = $userModel->getAllPermissions();
        $this->info("User '$user' now has the following permissions: ".implode(', ', $permissions->pluck('name')->toArray()));
        // Optionally, you can also display the user's roles after assignment
        $roles = $userModel->getRoleNames();
        $this->info("User '$user' has the following roles: ".implode(', ', $roles->toArray()));
    }
}
