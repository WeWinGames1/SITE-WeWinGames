<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\TicketCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if Resume category doesn't already exist
        if (!TicketCategory::where('slug', 'resume')->exists()) {
            TicketCategory::create([
                'name' => 'Resume',
                'slug' => 'resume',
                'description' => 'Job application and resume submissions',
                'is_active' => true,
                'sort_order' => 10,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        TicketCategory::where('slug', 'resume')->delete();
    }
};