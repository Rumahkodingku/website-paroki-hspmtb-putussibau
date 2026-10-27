<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->whereIn('key', [
            'home_hero_title',
            'home_hero_description',
        ])->delete();
    }

    public function down(): void
    {
        // The rows are intentionally not restored: the hero now derives its
        // text from the parish identity settings instead.
    }
};
