<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livestreams', function (Blueprint $table): void {
            $table->foreignId('livestream_category_id')
                ->nullable()
                ->after('location_id')
                ->constrained('livestream_categories')
                ->nullOnDelete();
        });

        // Migrate the legacy enum column into the new category table.
        $categories = DB::table('livestream_categories')
            ->pluck('id', 'slug');

        foreach ($categories as $slug => $id) {
            DB::table('livestreams')
                ->where('category', $slug)
                ->update(['livestream_category_id' => $id]);
        }

        Schema::table('livestreams', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('livestreams', function (Blueprint $table): void {
            $table->enum('category', ['liturgi', 'hari_raya', 'devosi', 'kegiatan'])->nullable()->after('location_id');
        });

        $categories = DB::table('livestream_categories')->get(['id', 'slug']);

        foreach ($categories as $category) {
            DB::table('livestreams')
                ->where('livestream_category_id', $category->id)
                ->update(['category' => $category->slug]);
        }

        Schema::table('livestreams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('livestream_category_id');
        });
    }
};
