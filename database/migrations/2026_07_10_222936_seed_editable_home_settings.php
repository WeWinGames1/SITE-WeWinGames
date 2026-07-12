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
        $settings = [
            [
                'key' => 'enable_editable_home',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'homepage',
                'label' => 'Use editable homepage',
                'description' => 'When on, the homepage is rendered from the selected editable landing page instead of the built-in design.',
            ],
            [
                'key' => 'home_landing_page_id',
                'value' => null,
                'type' => 'integer',
                'group' => 'homepage',
                'label' => 'Editable homepage source',
                'description' => 'ID of the landing page to render as the homepage (used when "Use editable homepage" is on).',
            ],
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('site_settings')->where('key', $setting['key'])->exists();
            if (! $exists) {
                DB::table('site_settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', ['enable_editable_home', 'home_landing_page_id'])->delete();
    }
};
