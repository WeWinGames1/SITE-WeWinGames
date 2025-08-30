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
        Schema::table('operators', function (Blueprint $table) {
            if (! Schema::hasColumn('operators', 'slug')) {
                $table->string('slug')->after('name')->nullable();
                $table->index('slug');
            }
        });

        // Update existing records to have slugs
        $operators = DB::table('operators')->get();
        foreach ($operators as $operator) {
            DB::table('operators')
                ->where('id', $operator->id)
                ->update(['slug' => Str::slug($operator->name)]);
        }

        // Make slug not nullable after populating
        Schema::table('operators', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
