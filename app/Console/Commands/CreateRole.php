<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class CreateRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-role {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new role in the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $name = $this->argument('name');
        if (Role::where('name', $name)->exists()) {
            $this->error("Role '$name' already exists.");

            return;
        }
        $role = Role::create(['name' => $name]);
        $this->info("Role '$name' created successfully.");
    }
}
