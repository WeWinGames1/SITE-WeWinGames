<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use raw SQL to modify ENUM columns
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE support_tickets MODIFY COLUMN status ENUM('open', 'pending', 'resolved', 'closed', 'user-requests-close') DEFAULT 'open'");
        }
        // SQLite doesn't support ENUM, so it's already a varchar and will accept any value
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // First update any tickets with user-requests-close to pending
            DB::table('support_tickets')
                ->where('status', 'user-requests-close')
                ->update(['status' => 'pending']);

            // Then revert the ENUM
            DB::statement("ALTER TABLE support_tickets MODIFY COLUMN status ENUM('open', 'pending', 'resolved', 'closed') DEFAULT 'open'");
        }
    }
};
