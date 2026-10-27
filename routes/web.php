<?php

use App\Http\Controllers\Agenda\EventController;
use App\Http\Controllers\Agenda\EventIcsController;
use App\Http\Controllers\Agenda\PublicEventController;
use App\Http\Controllers\Area\AreaController;
use App\Http\Controllers\Area\PublicAreaController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Document\DocumentController;
use App\Http\Controllers\Document\PublicDocumentController;
use App\Http\Controllers\Galeri\GalleryController;
use App\Http\Controllers\Galeri\GalleryPhotoController;
use App\Http\Controllers\Galeri\PublicGalleryController;
use App\Http\Controllers\Home\PublicHomeController;
use App\Http\Controllers\Komunitas\CommunityController;
use App\Http\Controllers\Komunitas\PublicCommunityController;
use App\Http\Controllers\MassSchedule\MassScheduleController;
use App\Http\Controllers\MassSchedule\PublicMassScheduleController;
use App\Http\Controllers\MassSchedule\ScheduleNoticeController;
use App\Http\Controllers\MassSchedule\SpecialMassController;
use App\Http\Controllers\MasterData\CommunityCategoryController;
use App\Http\Controllers\MasterData\DocumentCategoryController;
use App\Http\Controllers\MasterData\EventCategoryController;
use App\Http\Controllers\MasterData\LocationController;
use App\Http\Controllers\MasterData\PostCategoryController;
use App\Http\Controllers\MasterData\ServiceCategoryController;
use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\News\PostController;
use App\Http\Controllers\News\PostPreviewController;
use App\Http\Controllers\News\PublicPostController;
use App\Http\Controllers\Pelayanan\PublicServiceController;
use App\Http\Controllers\Pelayanan\ServiceController;
use App\Http\Controllers\Pelayanan\ServiceFaqController;
use App\Http\Controllers\Pelayanan\ServiceRequirementController;
use App\Http\Controllers\Pelayanan\ServiceStepController;
use App\Http\Controllers\Profil\ParishBoardMemberController;
use App\Http\Controllers\Profil\ParishBoardPeriodController;
use App\Http\Controllers\Profil\ParishHistoryItemController;
use App\Http\Controllers\Profil\ParishProfileController;
use App\Http\Controllers\Profil\ParishServiceAreaController;
use App\Http\Controllers\Profil\PastorAssignmentController;
use App\Http\Controllers\Profil\PastorController;
use App\Http\Controllers\Profil\PublicParishProfileController;
use App\Http\Controllers\Public\PublicLegalController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAdminAccess;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', PublicHomeController::class)->name('home');

Route::get('/profil', [PublicParishProfileController::class, 'index'])->name('profil.index');
Route::get('/profil/sejarah', [PublicParishProfileController::class, 'sejarah'])->name('profil.sejarah');
Route::get('/profil/wilayah', [PublicParishProfileController::class, 'wilayah'])->name('profil.wilayah');
Route::get('/profil/pastor', [PublicParishProfileController::class, 'pastor'])->name('profil.pastor');
Route::get('/profil/struktur', [PublicParishProfileController::class, 'struktur'])->name('profil.struktur');

Route::get('/jadwal-misa', [PublicMassScheduleController::class, 'index'])->name('jadwal-misa.index');

Route::get('/berita', [PublicPostController::class, 'index'])->name('berita.index');

Route::get('/berita/{post:slug}', [PublicPostController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('berita.show');

Route::get('/agenda', [PublicEventController::class, 'index'])->name('agenda.index');
Route::get('/agenda/{event:slug}/ics', [EventIcsController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('agenda.ics');

Route::get('/agenda/{event:slug}', [PublicEventController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('agenda.show');

Route::get('/pelayanan', [PublicServiceController::class, 'index'])->name('pelayanan.index');
Route::get('/pelayanan/{service:slug}', [PublicServiceController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('pelayanan.show');

Route::get('/komunitas', [PublicCommunityController::class, 'index'])->name('komunitas.index');
Route::get('/komunitas/{community:slug}', [PublicCommunityController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('komunitas.show');

Route::get('/profil/area', [PublicAreaController::class, 'index'])->name('area.index');
Route::get('/profil/area/{area:slug}', [PublicAreaController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('area.show');

Route::get('/galeri', [PublicGalleryController::class, 'index'])->name('galeri.index');
Route::get('/galeri/{gallery:slug}', [PublicGalleryController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('galeri.show');

Route::get('/dokumen', [PublicDocumentController::class, 'index'])->name('dokumen.index');
Route::get('/dokumen/{document}/unduh', [PublicDocumentController::class, 'download'])
    ->where('document', '[0-9]+')
    ->name('dokumen.unduh');

Route::inertia('/kontak', 'public/kontak')->name('kontak.index');

Route::get('/kebijakan-privasi', [PublicLegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/syarat-ketentuan', [PublicLegalController::class, 'terms'])->name('legal.terms');

Route::inertia('/design-system', 'public/design-system')->name('design-system.index');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| Everything under /admin is the CMS, behind the four middleware layers PRD
| AUTH-R1 requires. Sections are in the order they were registered before the
| files were merged; keep it that way, because matching depends on it.
|
*/

Route::middleware(['auth', 'verified', EnsureAccountIsActive::class, EnsureAdminAccess::class])
    ->prefix('admin')
    ->group(function () {
        /*
         * ------------------------------------------------------------------
         * Dashboard
         * ------------------------------------------------------------------
         */
        Route::get('/', DashboardController::class)->name('dashboard');

        /*
         * ------------------------------------------------------------------
         * Pengaturan Situs
         * ------------------------------------------------------------------
         */
        Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [SettingsController::class, 'update'])->name('settings.update');

        /*
         * ------------------------------------------------------------------
         * Media
         * ------------------------------------------------------------------
         */
        Route::post('/media', [MediaController::class, 'store'])->name('media.store');
        Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

        /*
         * ------------------------------------------------------------------
         * Akun (/admin/akun)
         * ------------------------------------------------------------------
         |
         | PRD 5.2 puts profile, password and Super Admin account management
         | under /admin/akun. These are admin pages, not public settings, so
         | they sit inside the same middleware group as everything else here.
         |
         | There is no route for deleting an account. PRD section 8 forbids
         | removing the signed-in account and forbids deactivating the last
         | active Super Admin, and this route had no {user} parameter, so it
         | could only ever delete the caller. Removing it is the only way to
         | satisfy that rule until real Super Admin account management arrives
         | with its own policy.
         |
         */
        Route::redirect('/akun', 'profile');

        Route::get('/akun/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/akun/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/akun/security', [SecurityController::class, 'edit'])
            ->middleware(RequirePassword::class)
            ->name('security.edit');

        Route::put('/akun/password', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');

        Route::inertia('/akun/appearance', 'admin/akun/appearance')->name('appearance.edit');

        /*
         * ------------------------------------------------------------------
         * Jadwal Misa
         * ------------------------------------------------------------------
         |
         | The module root is a redirect, not a page. Four counts with nowhere
         | to go is not a destination, and the four lists are what the module is
         | for. The URL stays because it is what PRD 5.2 names and because every
         | breadcrumb in the module still points here. See D-42.
         |
         */
        Route::get('/jadwal-misa', fn () => to_route('mass-schedules.list'))->name('mass-schedules.index');

        // Misa Rutin
        Route::get('/jadwal-misa/misa-rutin', [MassScheduleController::class, 'misaRutin'])->name('mass-schedules.list');
        Route::get('/jadwal-misa/misa-rutin/tambah', [MassScheduleController::class, 'create'])->name('mass-schedules.create');
        Route::post('/jadwal-misa/misa-rutin', [MassScheduleController::class, 'store'])->name('mass-schedules.store');
        Route::get('/jadwal-misa/misa-rutin/{mass_schedule}/ubah', [MassScheduleController::class, 'edit'])->name('mass-schedules.edit');
        Route::patch('/jadwal-misa/misa-rutin/{mass_schedule}', [MassScheduleController::class, 'update'])->name('mass-schedules.update');
        Route::delete('/jadwal-misa/misa-rutin/{mass_schedule}', [MassScheduleController::class, 'destroy'])->name('mass-schedules.destroy');

        Route::post('/jadwal-misa/misa-rutin/{mass_schedule}/duplikat', [MassScheduleController::class, 'duplicate'])
            ->name('mass-schedules.duplicate');

        // Misa Khusus
        Route::get('/jadwal-misa/misa-khusus', [SpecialMassController::class, 'index'])->name('special-masses.index');
        Route::get('/jadwal-misa/misa-khusus/tambah', [SpecialMassController::class, 'create'])->name('special-masses.create');
        Route::post('/jadwal-misa/misa-khusus', [SpecialMassController::class, 'store'])->name('special-masses.store');
        Route::get('/jadwal-misa/misa-khusus/{special_mass}/ubah', [SpecialMassController::class, 'edit'])->name('special-masses.edit');
        Route::patch('/jadwal-misa/misa-khusus/{special_mass}', [SpecialMassController::class, 'update'])->name('special-masses.update');
        Route::delete('/jadwal-misa/misa-khusus/{special_mass}', [SpecialMassController::class, 'destroy'])->name('special-masses.destroy');

        // Pemberitahuan Perubahan Jadwal
        Route::get('/jadwal-misa/pemberitahuan-jadwal', [ScheduleNoticeController::class, 'index'])->name('schedule-notices.index');
        Route::get('/jadwal-misa/pemberitahuan-jadwal/tambah', [ScheduleNoticeController::class, 'create'])->name('schedule-notices.create');
        Route::post('/jadwal-misa/pemberitahuan-jadwal', [ScheduleNoticeController::class, 'store'])->name('schedule-notices.store');
        Route::get('/jadwal-misa/pemberitahuan-jadwal/{schedule_notice}/ubah', [ScheduleNoticeController::class, 'edit'])->name('schedule-notices.edit');
        Route::patch('/jadwal-misa/pemberitahuan-jadwal/{schedule_notice}', [ScheduleNoticeController::class, 'update'])->name('schedule-notices.update');
        Route::delete('/jadwal-misa/pemberitahuan-jadwal/{schedule_notice}', [ScheduleNoticeController::class, 'destroy'])->name('schedule-notices.destroy');

        /*
         * ------------------------------------------------------------------
         * Master Data
         * ------------------------------------------------------------------
         |
         | The three lookup tables: where things happen, and how articles and
         | activities are grouped. Route NAMES are unchanged from before they
         | moved here (`locations.*`, `post-categories.*`,
         | `event-categories.*`) — only the URIs moved. See D-48.
         |
         */
        // Lokasi
        Route::get('/master-data/lokasi', [LocationController::class, 'index'])->name('locations.index');
        Route::get('/master-data/lokasi/tambah', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/master-data/lokasi', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/master-data/lokasi/{location}/ubah', [LocationController::class, 'edit'])->name('locations.edit');
        Route::patch('/master-data/lokasi/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/master-data/lokasi/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

        // Kategori Berita
        Route::get('/master-data/kategori-berita', [PostCategoryController::class, 'index'])->name('post-categories.index');
        Route::get('/master-data/kategori-berita/tambah', [PostCategoryController::class, 'create'])->name('post-categories.create');
        Route::post('/master-data/kategori-berita', [PostCategoryController::class, 'store'])->name('post-categories.store');
        Route::get('/master-data/kategori-berita/{post_category}/ubah', [PostCategoryController::class, 'edit'])->name('post-categories.edit');
        Route::patch('/master-data/kategori-berita/{post_category}', [PostCategoryController::class, 'update'])->name('post-categories.update');
        Route::delete('/master-data/kategori-berita/{post_category}', [PostCategoryController::class, 'destroy'])->name('post-categories.destroy');

        // Kategori Agenda
        Route::get('/master-data/kategori-agenda', [EventCategoryController::class, 'index'])->name('event-categories.index');
        Route::get('/master-data/kategori-agenda/tambah', [EventCategoryController::class, 'create'])->name('event-categories.create');
        Route::post('/master-data/kategori-agenda', [EventCategoryController::class, 'store'])->name('event-categories.store');
        Route::get('/master-data/kategori-agenda/{event_category}/ubah', [EventCategoryController::class, 'edit'])->name('event-categories.edit');
        Route::patch('/master-data/kategori-agenda/{event_category}', [EventCategoryController::class, 'update'])->name('event-categories.update');
        Route::delete('/master-data/kategori-agenda/{event_category}', [EventCategoryController::class, 'destroy'])->name('event-categories.destroy');

        // Kategori Pelayanan — Master Data since D-54 (was a PRD ENUM).
        Route::get('/master-data/kategori-pelayanan', [ServiceCategoryController::class, 'index'])->name('service-categories.index');
        Route::get('/master-data/kategori-pelayanan/tambah', [ServiceCategoryController::class, 'create'])->name('service-categories.create');
        Route::post('/master-data/kategori-pelayanan', [ServiceCategoryController::class, 'store'])->name('service-categories.store');
        Route::get('/master-data/kategori-pelayanan/{service_category}/ubah', [ServiceCategoryController::class, 'edit'])->name('service-categories.edit');
        Route::patch('/master-data/kategori-pelayanan/{service_category}', [ServiceCategoryController::class, 'update'])->name('service-categories.update');
        Route::delete('/master-data/kategori-pelayanan/{service_category}', [ServiceCategoryController::class, 'destroy'])->name('service-categories.destroy');

        // Kategori Komunitas — Master Data since Phase 07 (D-55 point 5).
        Route::get('/master-data/kategori-komunitas', [CommunityCategoryController::class, 'index'])->name('community-categories.index');
        Route::get('/master-data/kategori-komunitas/tambah', [CommunityCategoryController::class, 'create'])->name('community-categories.create');
        Route::post('/master-data/kategori-komunitas', [CommunityCategoryController::class, 'store'])->name('community-categories.store');
        Route::get('/master-data/kategori-komunitas/{community_category}/ubah', [CommunityCategoryController::class, 'edit'])->name('community-categories.edit');
        Route::patch('/master-data/kategori-komunitas/{community_category}', [CommunityCategoryController::class, 'update'])->name('community-categories.update');
        Route::delete('/master-data/kategori-komunitas/{community_category}', [CommunityCategoryController::class, 'destroy'])->name('community-categories.destroy');

        // Kategori Dokumen — Master Data since Phase 08B (D-66).
        Route::get('/master-data/kategori-dokumen', [DocumentCategoryController::class, 'index'])->name('document-categories.index');
        Route::get('/master-data/kategori-dokumen/tambah', [DocumentCategoryController::class, 'create'])->name('document-categories.create');
        Route::post('/master-data/kategori-dokumen', [DocumentCategoryController::class, 'store'])->name('document-categories.store');
        Route::get('/master-data/kategori-dokumen/{document_category}/ubah', [DocumentCategoryController::class, 'edit'])->name('document-categories.edit');
        Route::patch('/master-data/kategori-dokumen/{document_category}', [DocumentCategoryController::class, 'update'])->name('document-categories.update');
        Route::delete('/master-data/kategori-dokumen/{document_category}', [DocumentCategoryController::class, 'destroy'])->name('document-categories.destroy');

        /*
         * ------------------------------------------------------------------
         * Komunitas
         * ------------------------------------------------------------------
         |
         | `/admin/komunitas` renders the list itself — a flat sidebar link, no
         | overview and no redirect (D-55 point 5). The areas a community serves
         | are edited inside the community form and gated on `communities.update`
         | through CommunityPolicy; the pivot has no endpoint of its own.
         |
         */
        Route::get('/komunitas', [CommunityController::class, 'index'])->name('communities.index');
        Route::get('/komunitas/tambah', [CommunityController::class, 'create'])->name('communities.create');
        Route::post('/komunitas', [CommunityController::class, 'store'])->name('communities.store');
        Route::get('/komunitas/{community}/ubah', [CommunityController::class, 'edit'])->name('communities.edit');
        Route::patch('/komunitas/{community}', [CommunityController::class, 'update'])->name('communities.update');
        Route::delete('/komunitas/{community}', [CommunityController::class, 'destroy'])->name('communities.destroy');

        /*
         * ------------------------------------------------------------------
         * Area
         * ------------------------------------------------------------------
         |
         | Its own module, not part of Master Data or Komunitas: the area
         | hierarchy is operational data with its own public pages (D-57). The
         | route NAME stays `areas.*`.
         |
         */
        Route::get('/area', [AreaController::class, 'index'])->name('areas.index');
        Route::get('/area/tambah', [AreaController::class, 'create'])->name('areas.create');
        Route::post('/area', [AreaController::class, 'store'])->name('areas.store');
        Route::get('/area/{area}/ubah', [AreaController::class, 'edit'])->name('areas.edit');
        Route::patch('/area/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::delete('/area/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');

        /*
         * ------------------------------------------------------------------
         * Galeri
         * ------------------------------------------------------------------
         |
         | The album CRUD plus a photo manager screen (D-5, D-65). Every photo
         | endpoint is gated on `galleries.update` through GalleryAlbumPolicy: a
         | photo has no list page, so it has no permission group of its own.
         |
         */
        Route::get('/galeri', [GalleryController::class, 'index'])->name('galleries.index');
        Route::get('/galeri/tambah', [GalleryController::class, 'create'])->name('galleries.create');
        Route::post('/galeri', [GalleryController::class, 'store'])->name('galleries.store');
        Route::get('/galeri/{gallery}/ubah', [GalleryController::class, 'edit'])->name('galleries.edit');
        Route::patch('/galeri/{gallery}', [GalleryController::class, 'update'])->name('galleries.update');
        Route::delete('/galeri/{gallery}', [GalleryController::class, 'destroy'])->name('galleries.destroy');
        Route::get('/galeri/{gallery}/foto', [GalleryController::class, 'photos'])->name('galleries.photos');

        Route::post('/galeri/{gallery}/foto', [GalleryPhotoController::class, 'store'])->name('gallery-photos.store');
        Route::post('/galeri/{gallery}/foto/urutkan', [GalleryPhotoController::class, 'reorder'])->name('gallery-photos.reorder');
        Route::post('/galeri/{gallery}/foto/sampul', [GalleryPhotoController::class, 'cover'])->name('gallery-photos.cover');
        Route::patch('/galeri/{gallery}/foto/{photo}', [GalleryPhotoController::class, 'update'])->name('gallery-photos.update');
        Route::delete('/galeri/{gallery}/foto/{photo}', [GalleryPhotoController::class, 'destroy'])->name('gallery-photos.destroy');

        /*
         * ------------------------------------------------------------------
         * Dokumen
         * ------------------------------------------------------------------
         |
         | The list is the module root: PRD 5.2 names /admin/download, but the
         | feature is called Dokumen now, so /admin/download is a named legacy
         | redirect and this is the destination.
         |
         */
        Route::get('/dokumen', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/dokumen/tambah', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('/dokumen', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/dokumen/{document}/ubah', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::patch('/dokumen/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('/dokumen/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/dokumen/{document}/pratinjau', [DocumentController::class, 'preview'])->name('documents.preview');

        /*
         * ------------------------------------------------------------------
         * Profil Paroki
         * ------------------------------------------------------------------
         |
         | Route names never collide with the public `profil.*` set (D-49): the
         | admin side uses `parish-*` / `pastors.*` / `board-*`. The module root
         | is a named redirect to the first section, because there is no overview
         | page (D-52). Every redirect must be named or Wayfinder fails to build.
         |
         */
        Route::get('/profil', fn () => to_route('parish-profile.edit'))->name('parish-profile.index');

        // Informasi Profil
        Route::get('/profil/informasi', [ParishProfileController::class, 'edit'])->name('parish-profile.edit');
        Route::put('/profil/informasi', [ParishProfileController::class, 'update'])->name('parish-profile.update');

        // Sejarah Paroki
        Route::get('/profil/sejarah', [ParishHistoryItemController::class, 'index'])->name('parish-histories.index');
        Route::put('/profil/sejarah/narasi', [ParishHistoryItemController::class, 'updateNarrative'])->name('parish-histories.narrative.update');
        Route::get('/profil/sejarah/tambah', [ParishHistoryItemController::class, 'create'])->name('parish-histories.create');
        Route::post('/profil/sejarah', [ParishHistoryItemController::class, 'store'])->name('parish-histories.store');
        Route::get('/profil/sejarah/{history_item}/ubah', [ParishHistoryItemController::class, 'edit'])->name('parish-histories.edit');
        Route::patch('/profil/sejarah/{history_item}', [ParishHistoryItemController::class, 'update'])->name('parish-histories.update');
        Route::delete('/profil/sejarah/{history_item}', [ParishHistoryItemController::class, 'destroy'])->name('parish-histories.destroy');

        // Wilayah Pelayanan
        Route::get('/profil/wilayah', [ParishServiceAreaController::class, 'edit'])->name('parish-service-area.edit');
        Route::put('/profil/wilayah', [ParishServiceAreaController::class, 'update'])->name('parish-service-area.update');

        // Pastor Paroki
        Route::get('/profil/pastor', [PastorController::class, 'index'])->name('pastors.index');
        Route::get('/profil/pastor/tambah', [PastorController::class, 'create'])->name('pastors.create');
        Route::post('/profil/pastor', [PastorController::class, 'store'])->name('pastors.store');
        Route::get('/profil/pastor/{pastor}/ubah', [PastorController::class, 'edit'])->name('pastors.edit');
        Route::patch('/profil/pastor/{pastor}', [PastorController::class, 'update'])->name('pastors.update');
        Route::delete('/profil/pastor/{pastor}', [PastorController::class, 'destroy'])->name('pastors.destroy');

        Route::get('/profil/pastor/{pastor}/penugasan/tambah', [PastorAssignmentController::class, 'create'])->name('pastor-assignments.create');
        Route::post('/profil/pastor/{pastor}/penugasan', [PastorAssignmentController::class, 'store'])->name('pastor-assignments.store');
        Route::get('/profil/pastor/{pastor}/penugasan/{assignment}/ubah', [PastorAssignmentController::class, 'edit'])->name('pastor-assignments.edit');
        Route::patch('/profil/pastor/{pastor}/penugasan/{assignment}', [PastorAssignmentController::class, 'update'])->name('pastor-assignments.update');
        Route::delete('/profil/pastor/{pastor}/penugasan/{assignment}', [PastorAssignmentController::class, 'destroy'])->name('pastor-assignments.destroy');
        Route::post('/profil/pastor/{pastor}/penugasan/{assignment}/aktifkan', [PastorAssignmentController::class, 'activate'])->name('pastor-assignments.activate');

        // Struktur Kepengurusan
        Route::get('/profil/struktur', [ParishBoardPeriodController::class, 'index'])->name('board-periods.index');
        Route::get('/profil/struktur/periode/tambah', [ParishBoardPeriodController::class, 'create'])->name('board-periods.create');
        Route::post('/profil/struktur/periode', [ParishBoardPeriodController::class, 'store'])->name('board-periods.store');
        Route::get('/profil/struktur/periode/{period}/ubah', [ParishBoardPeriodController::class, 'edit'])->name('board-periods.edit');
        Route::patch('/profil/struktur/periode/{period}', [ParishBoardPeriodController::class, 'update'])->name('board-periods.update');
        Route::delete('/profil/struktur/periode/{period}', [ParishBoardPeriodController::class, 'destroy'])->name('board-periods.destroy');
        Route::post('/profil/struktur/periode/{period}/aktifkan', [ParishBoardPeriodController::class, 'activate'])->name('board-periods.activate');

        Route::get('/profil/struktur/periode/{period}/anggota/tambah', [ParishBoardMemberController::class, 'create'])->name('board-members.create');
        Route::post('/profil/struktur/periode/{period}/anggota', [ParishBoardMemberController::class, 'store'])->name('board-members.store');
        Route::get('/profil/struktur/periode/{period}/anggota/{member}/ubah', [ParishBoardMemberController::class, 'edit'])->name('board-members.edit');
        Route::patch('/profil/struktur/periode/{period}/anggota/{member}', [ParishBoardMemberController::class, 'update'])->name('board-members.update');
        Route::delete('/profil/struktur/periode/{period}/anggota/{member}', [ParishBoardMemberController::class, 'destroy'])->name('board-members.destroy');

        /*
         * ------------------------------------------------------------------
         * Berita & Artikel
         * ------------------------------------------------------------------
         |
         | `.list` rather than `.index` on the real page, and `.index` on the
         | module root as a redirect — the shape Phase 04 settled on. PRD 5.2
         | names /admin/berita and every breadcrumb in the module points at it,
         | but a redirect is not a destination and the list is where an admin
         | actually wants to land. See D-42.
         |
         */
        Route::get('/berita', fn () => to_route('posts.list'))->name('posts.index');
        Route::get('/berita/daftar', [PostController::class, 'index'])->name('posts.list');
        Route::get('/berita/tambah', [PostController::class, 'create'])->name('posts.create');
        Route::post('/berita', [PostController::class, 'store'])->name('posts.store');
        Route::get('/berita/{post}/ubah', [PostController::class, 'edit'])->name('posts.edit');
        Route::patch('/berita/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/berita/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::get('/berita/{post}/pratinjau', [PostPreviewController::class, 'show'])->name('posts.preview');

        /*
         * ------------------------------------------------------------------
         * Agenda Kegiatan
         * ------------------------------------------------------------------
         |
         | `.list` for the same reason as `posts.list`: `.index` is the module
         | root redirect PRD 5.2 names.
         |
         | Duplication is a POST. Roadmap 9 says avoid mutating endpoints on
         | GET, and Phase 04's own duplicate route is a POST for the same
         | reason.
         |
         */
        Route::get('/agenda', fn () => to_route('events.list'))->name('events.index');
        Route::get('/agenda/daftar', [EventController::class, 'index'])->name('events.list');
        Route::get('/agenda/tambah', [EventController::class, 'create'])->name('events.create');
        Route::post('/agenda', [EventController::class, 'store'])->name('events.store');
        Route::get('/agenda/{event}/ubah', [EventController::class, 'edit'])->name('events.edit');
        Route::patch('/agenda/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/agenda/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::post('/agenda/{event}/duplikat', [EventController::class, 'duplicate'])
            ->name('events.duplicate');

        /*
         * ------------------------------------------------------------------
         * Pelayanan
         * ------------------------------------------------------------------
         |
         | `/admin/pelayanan` renders the list itself — there is no overview and
         | no redirect, because roadmap 6B-041 wants the sidebar item to open the
         | list directly (D-54).
         |
         | The requirement, step and FAQ endpoints are nested under a service and
         | gated on `services.update` through ServicePolicy; they have no
         | permission group of their own. The reorder endpoints are POST for the
         | same reason Phase 04's duplicate endpoint is: a mutation is never a GET.
         |
         */
        Route::get('/pelayanan', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/pelayanan/tambah', [ServiceController::class, 'create'])->name('services.create');
        Route::post('/pelayanan', [ServiceController::class, 'store'])->name('services.store');
        Route::get('/pelayanan/{service}/ubah', [ServiceController::class, 'edit'])->name('services.edit');
        Route::patch('/pelayanan/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/pelayanan/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        // Persyaratan
        Route::post('/pelayanan/{service}/persyaratan', [ServiceRequirementController::class, 'store'])->name('service-requirements.store');
        Route::post('/pelayanan/{service}/persyaratan/urutkan', [ServiceRequirementController::class, 'reorder'])->name('service-requirements.reorder');
        Route::patch('/pelayanan/{service}/persyaratan/{requirement}', [ServiceRequirementController::class, 'update'])->name('service-requirements.update');
        Route::delete('/pelayanan/{service}/persyaratan/{requirement}', [ServiceRequirementController::class, 'destroy'])->name('service-requirements.destroy');

        // Prosedur
        Route::post('/pelayanan/{service}/prosedur', [ServiceStepController::class, 'store'])->name('service-steps.store');
        Route::post('/pelayanan/{service}/prosedur/urutkan', [ServiceStepController::class, 'reorder'])->name('service-steps.reorder');
        Route::patch('/pelayanan/{service}/prosedur/{step}', [ServiceStepController::class, 'update'])->name('service-steps.update');
        Route::delete('/pelayanan/{service}/prosedur/{step}', [ServiceStepController::class, 'destroy'])->name('service-steps.destroy');

        // FAQ
        Route::post('/pelayanan/{service}/faq', [ServiceFaqController::class, 'store'])->name('service-faqs.store');
        Route::post('/pelayanan/{service}/faq/urutkan', [ServiceFaqController::class, 'reorder'])->name('service-faqs.reorder');
        Route::patch('/pelayanan/{service}/faq/{faq}', [ServiceFaqController::class, 'update'])->name('service-faqs.update');
        Route::delete('/pelayanan/{service}/faq/{faq}', [ServiceFaqController::class, 'destroy'])->name('service-faqs.destroy');
    });

/*
|--------------------------------------------------------------------------
| The URLs Master Data used to have
|--------------------------------------------------------------------------
|
| Every one of these was live before D-48 and is still reachable, because a
| bookmarked link is the difference between "the menu moved" and "the parish
| secretary's link is broken".
|
| Outside the middleware group on purpose: a guest following one gets the 302
| and *then* meets the login screen, rather than being sent straight to it.
| `LocationRedirectTest` asserts that a guest sees the redirect.
|
| Named on purpose, and that is load-bearing rather than documentation — see the
| header of this file and D-48 §3.
|
*/

Route::redirect('/admin/jadwal-misa/lokasi', '/admin/master-data/lokasi')
    ->name('legacy.locations.index');
Route::redirect('/admin/jadwal-misa/lokasi/tambah', '/admin/master-data/lokasi/tambah')
    ->name('legacy.locations.create');
Route::redirect('/admin/jadwal-misa/lokasi/{location}/ubah', '/admin/master-data/lokasi/{location}/ubah')
    ->name('legacy.locations.edit');

Route::redirect('/admin/kategori-berita', '/admin/master-data/kategori-berita')
    ->name('legacy.post-categories.index');
Route::redirect('/admin/kategori-berita/tambah', '/admin/master-data/kategori-berita/tambah')
    ->name('legacy.post-categories.create');
Route::redirect('/admin/kategori-berita/{post_category}/ubah', '/admin/master-data/kategori-berita/{post_category}/ubah')
    ->name('legacy.post-categories.edit');

Route::redirect('/admin/kategori-agenda', '/admin/master-data/kategori-agenda')
    ->name('legacy.event-categories.index');
Route::redirect('/admin/kategori-agenda/tambah', '/admin/master-data/kategori-agenda/tambah')
    ->name('legacy.event-categories.create');
Route::redirect('/admin/kategori-agenda/{event_category}/ubah', '/admin/master-data/kategori-agenda/{event_category}/ubah')
    ->name('legacy.event-categories.edit');

/*
|--------------------------------------------------------------------------
| The Download URLs Dokumen replaced
|--------------------------------------------------------------------------
|
| /download and /download/{id}/unduh were live placeholders from Phase 03 and
| are the links a bookmark may still hold. They are permanent redirects, named
| like the other legacy routes so Wayfinder does not generate a duplicate
| export (D-48 §3).
|
*/

Route::redirect('/download', '/dokumen', 301)
    ->name('legacy.download.index');
Route::redirect('/download/{id}/unduh', '/dokumen/{id}/unduh', 301)
    ->where('id', '[0-9]+')
    ->name('legacy.download.unduh');
Route::redirect('/admin/download', '/admin/dokumen', 301)
    ->name('legacy.download.admin');
Route::redirect('/admin/kategori-download', '/admin/master-data/kategori-dokumen', 301)
    ->name('legacy.download-categories.index');
