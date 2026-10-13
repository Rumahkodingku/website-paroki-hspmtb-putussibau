<?php

use App\Enums\PastorPosition;
use App\Models\Media;
use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryItem;
use App\Models\ParishProfile;
use App\Models\ParishServiceArea;
use App\Models\Pastor;
use App\Models\PastorAssignment;

/*
|--------------------------------------------------------------------------
| Profil Paroki — schema dan domain
|--------------------------------------------------------------------------
|
| Tujuh tabel Phase 6A dan model di atasnya. Yang diuji di sini bukan Eloquent
| bekerja, melainkan bahwa bentuk skemanya adalah bentuk yang diputuskan,
| karena itulah yang bisa dibatalkan Phase berikutnya dengan satu kolom.
|
| Dua yang paling penting:
|
|   - Tidak ada hierarki wilayah di parish_service_areas. Domain itu milik
|     modul Komunitas Phase 7 dan hanya ditampilkan di sini (D-52).
|   - Anggota kepengurusan ikut terhapus saat periodenya dihapus (CASCADE),
|     dan media selalu SET NULL agar konten tidak ikut hilang.
|
| @see docs/DECISIONS.md D-52
*/

/*
|--------------------------------------------------------------------------
| Schema
|--------------------------------------------------------------------------
*/

it('creates the seven tables', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue();
})->with([
    'parish_profiles',
    'parish_history_items',
    'parish_service_areas',
    'pastors',
    'pastor_assignments',
    'parish_board_periods',
    'parish_board_members',
]);

it('has no slug and no generated marker on any table', function (string $table) {
    // No public route binds by slug in this module, and no unique slot needs to
    // be released, so neither slug nor active_marker belongs here (D-52).
    expect(Schema::hasColumn($table, 'slug'))->toBeFalse()
        ->and(Schema::hasColumn($table, 'active_marker'))->toBeFalse()
        ->and(Schema::hasColumn($table, 'deleted_at'))->toBeFalse();
})->with([
    'parish_profiles',
    'parish_history_items',
    'parish_service_areas',
    'pastors',
    'pastor_assignments',
    'parish_board_periods',
    'parish_board_members',
]);

it('has no community foreign key anywhere in the module', function (string $table) {
    // Wilayah pelayanan only displays a narrative; the structured hierarchy is
    // Phase 07's and must not be duplicated here.
    $foreignTables = collect(Schema::getForeignKeys($table))
        ->pluck('foreign_table')
        ->all();

    expect($foreignTables)->not->toContain('communities');
})->with([
    'parish_service_areas',
    'parish_history_items',
]);

it('nulls a media reference rather than deleting the record', function () {
    $profile = ParishProfile::factory()->create();
    $media = Media::factory()->create();

    $profile->update(['logo_media_id' => $media->getKey()]);

    $media->delete();

    expect($profile->fresh()->logo_media_id)->toBeNull();
});

it('cascades members when a period is deleted', function () {
    $period = ParishBoardPeriod::factory()->create();
    ParishBoardMember::factory()->count(3)->create([
        'parish_board_period_id' => $period->getKey(),
    ]);

    $period->delete();

    expect(ParishBoardMember::count())->toBe(0);
});

it('cascades assignments when a pastor is deleted', function () {
    $pastor = Pastor::factory()->create();
    PastorAssignment::factory()->count(2)->create(['pastor_id' => $pastor->getKey()]);

    $pastor->delete();

    expect(PastorAssignment::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Singleton
|--------------------------------------------------------------------------
*/

it('returns an unsaved profile when none exists yet', function () {
    $profile = ParishProfile::current();

    expect($profile->exists)->toBeFalse();
    // Reading does not write: a GET of the edit form must not create a row.
    expect(ParishProfile::count())->toBe(0);
});

it('returns the one profile, never a second', function () {
    $created = ParishProfile::factory()->create();

    expect(ParishProfile::current()->getKey())->toBe($created->getKey());
});

it('returns the same for the service area', function () {
    expect(ParishServiceArea::current()->exists)->toBeFalse();

    $created = ParishServiceArea::factory()->create();

    expect(ParishServiceArea::current()->getKey())->toBe($created->getKey());
});

/*
|--------------------------------------------------------------------------
| Publikasi
|--------------------------------------------------------------------------
*/

it('publishes only published history items', function () {
    $published = ParishHistoryItem::factory()->create();
    ParishHistoryItem::factory()->unpublished()->create();

    expect(ParishHistoryItem::published()->pluck('id')->all())->toBe([$published->getKey()]);
});

it('orders history by sort_order, then year, then id', function () {
    $second = ParishHistoryItem::factory()->withYear(2000)->ordered(1)->create();
    $first = ParishHistoryItem::factory()->withYear(2010)->ordered(0)->create();
    $third = ParishHistoryItem::factory()->withYear(1990)->ordered(1)->create();

    expect(ParishHistoryItem::ordered()->pluck('id')->all())
        ->toBe([$first->getKey(), $third->getKey(), $second->getKey()]);
});

it('publishes only published periods and members', function () {
    $period = ParishBoardPeriod::factory()->create();
    ParishBoardPeriod::factory()->unpublished()->create();
    ParishBoardMember::factory()->create(['parish_board_period_id' => $period->getKey()]);
    ParishBoardMember::factory()->unpublished()->create(['parish_board_period_id' => $period->getKey()]);

    expect(ParishBoardPeriod::published()->count())->toBe(1)
        ->and(ParishBoardMember::published()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Pastor
|--------------------------------------------------------------------------
*/

it('finds the pastor currently in office through the assignment', function () {
    $pastor = Pastor::factory()->create();
    PastorAssignment::factory()->for($pastor)->create(['is_current' => false]);
    $current = PastorAssignment::factory()->for($pastor)->current()->create();

    expect($pastor->currentAssignment->getKey())->toBe($current->getKey());
});

it('reports no current assignment for a pastor who has none', function () {
    $pastor = Pastor::factory()->create();
    PastorAssignment::factory()->for($pastor)->create(['is_current' => false]);

    expect($pastor->currentAssignment)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Enum
|--------------------------------------------------------------------------
*/

it('labels every pastor position through the language file', function () {
    foreach (PastorPosition::cases() as $position) {
        expect($position->label())->not->toBe("profil.positions.{$position->value}");
    }
});
