<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            if (!Schema::hasColumn('sports', 'slug')) {
                $table->string('slug')->after('name')->nullable();
                $table->index('slug');
            }
        });
        
        // Update existing records to have slugs
        $sports = DB::table('sports')->get();
        foreach ($sports as $sport) {
            DB::table('sports')
                ->where('id', $sport->id)
                ->update(['slug' => Str::slug($sport->name)]);
        }
        
        // Make slug not nullable after populating
        Schema::table('sports', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};