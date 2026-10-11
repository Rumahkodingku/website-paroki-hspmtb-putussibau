<?php

use App\Enums\LocationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Mass schedule tables
|--------------------------------------------------------------------------
|
| The four tables behind /jadwal-misa: where masses are celebrated, which masses
| recur every week, the one-off masses on a date, and the notices announcing a
| change to either.
|
| Three decisions here are not obvious from the column list, and each of them
| is a divergence or a trap worth stating rather than rediscovering:
|
| 1. There is no soft delete anywhere. PRD 9.5 marks locations and
|    special_masses as (SD) and ADM-05 requires soft delete on content tables,
|    but roadmap 4 forbids it where is_active is enough and 5.6 forbids a
|    permanent-delete endpoint. There is no delete endpoint in this module at
|    all, so a deleted_at column would never be written — see D-34.
|
| 2. mass_schedules.start_time is a wall clock, never a timestamp. PRD D-02 is
|    explicit: a weekly recurrence is not bound to a date, so converting it
|    would shift it by the UTC offset and move a 07.30 mass to 00.30. Only the
|    datetime columns are UTC (D-01). This is also why the model does not cast
|    start_time.
|
| 3. ON DELETE RESTRICT from mass_schedules and special_masses to locations,
|    roadmap 4.5: a location must never leave a schedule pointing at nothing.
|    Admin deactivates a location instead of deleting it, and MassSchedule's
|    active scope hides the schedules behind it (MASS-07). Notices go the other
|    way — SET NULL — because a notice outlives the thing it announced.
|
| @see docs/DECISIONS.md D-34
| @see docs/PRD.md 7.3, 9.5, D-01, D-02, D-09
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            // Makes ?lokasi=stasi-xxx readable and shareable. Roadmap 4.1 lists
            // it conditionally; 5.2 requires it to be validated. See D-34.
            $table->string('slug', 180)->unique();
            // The enum is the column type, so a new case is a schema change
            // rather than a value the column silently refuses.
            $table->enum('type', LocationType::values());
            $table->text('address')->nullable();
            // A pair or nothing: validated together, because a half-written
            // coordinate produces a map link pointing at the Gulf of Guinea.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('maps_url', 2048)->nullable();
            $table->text('notes')->nullable();
            // Deactivating is how a location is retired. PRD 7.3 admin: "CRUD
            // master lokasi (nonaktifkan, tidak hapus)".
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mass_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            // 0 = Minggu .. 6 = Sabtu, PRD Lampiran C.
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->string('name', 150);
            $table->string('language', 50)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // MASS-06 / PRD D-09. The second of the two layers the roadmap 5.2
            // asks for: the FormRequest gives a visitor a readable message, and
            // this guarantees the invariant when two requests race past it.
            $table->unique(
                ['location_id', 'day_of_week', 'start_time'],
                'mass_schedules_location_day_time_unique'
            );
            // The public query filters on is_active, groups by day and orders by
            // time within the day.
            $table->index(['day_of_week', 'start_time']);
        });

        Schema::create('special_masses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            // UTC, rendered in app.display_timezone (D-01).
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // MASS-02: the public tab shows active, not-yet-past masses, soonest
            // first. Both halves of that predicate are in the index because
            // neither is selective alone.
            $table->index(['start_at', 'is_active']);
            $table->index(['location_id', 'start_at']);
        });

        Schema::create('schedule_notices', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('message');

            // Roadmap 4.4: all three nullable, so a notice with no target applies
            // to everyone. Form requests enforce that at most one is filled.
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mass_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('special_mass_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('starts_at');
            // null means no end date, which is what MASS-05 says a missing
            // ends_at means.
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // MASS-05 filters on all three at once: is_active, starts_at <= now
            // and (ends_at is null or ends_at >= now).
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        // Reverse dependency order: the two tables holding foreign keys first.
        Schema::dropIfExists('schedule_notices');
        Schema::dropIfExists('special_masses');
        Schema::dropIfExists('mass_schedules');
        Schema::dropIfExists('locations');
    }
};
