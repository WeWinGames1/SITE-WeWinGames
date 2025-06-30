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
        Schema::table('support_tickets', function (Blueprint $table) {
            // Make user_id nullable for guest submissions
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Add guest submission fields
            $table->boolean('is_guest_submission')->default(false)->after('user_id');
            $table->string('guest_name')->nullable()->after('is_guest_submission');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->unsignedBigInteger('potential_user_id')->nullable()->after('guest_email');
            
            // Add foreign key for potential user
            $table->foreign('potential_user_id')->references('id')->on('users')->onDelete('set null');
            
            // Also rename title/description to subject/content if they exist
            if (Schema::hasColumn('support_tickets', 'title')) {
                $table->renameColumn('title', 'subject');
            }
            if (Schema::hasColumn('support_tickets', 'description')) {
                $table->renameColumn('description', 'content');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // Remove foreign key first
            $table->dropForeign(['potential_user_id']);
            
            // Remove guest fields
            $table->dropColumn(['is_guest_submission', 'guest_name', 'guest_email', 'potential_user_id']);
            
            // Make user_id required again
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Rename columns back if needed
            if (Schema::hasColumn('support_tickets', 'subject')) {
                $table->renameColumn('subject', 'title');
            }
            if (Schema::hasColumn('support_tickets', 'content')) {
                $table->renameColumn('content', 'description');
            }
        });
    }
};