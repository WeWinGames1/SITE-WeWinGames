<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class CreatePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-permission {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new permission to the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $name = $this->argument('name');
        if (Permission::where('name', $name)->exists()) {
            $this->error("Permission '$name' already exists.");

            return;
        }
        $permission = Permission::create(['name' => $name]);
        $this->info("Permission '$name' created successfully.");

    }
}
