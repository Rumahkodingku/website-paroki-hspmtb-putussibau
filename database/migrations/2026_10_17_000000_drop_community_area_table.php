<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Drop the community_area pivot
|--------------------------------------------------------------------------
|
| Phase 07 first linked a community to the areas it served (D-55). That link was
| removed by project decision: Stasi & Lingkungan is now its own module and a
| community no longer has a "wilayah pelayanan" (D-57). Dropping the pivot is the
| schema half; the model, validation, resources and public pages follow.
|
| A new migration rather than an edit of create_community_tables, which has
| already run on every machine. See AGENTS.md.
|
| @see docs/DECISIONS.md D-57
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('community_area');
    }

    public function down(): void
    {
        // Recreate exactly as create_community_tables had it, so a rollback
        // leaves the earlier schema intact.
        Schema::create('community_area', function (Blueprint $table) {
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();

            $table->primary(['community_id', 'area_id']);
        });
    }
};
