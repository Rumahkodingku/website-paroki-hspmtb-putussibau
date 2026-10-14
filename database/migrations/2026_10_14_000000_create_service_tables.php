<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Service (Pelayanan) tables
|--------------------------------------------------------------------------
|
| The five tables behind /pelayanan: the master categories, the services
| themselves, and the three ordered child lists a service carries (requirements,
| steps, FAQs).
|
| Four decisions here are not obvious from the column list, and each is a trap
| worth stating rather than rediscovering:
|
| 1. Category is a table, not the ENUM PRD 9.8 describes. Roadmap 6B 3.3/6.1
|    requires categories to be managed from Master Data, so
|    services.service_category_id is a plain FK. The PRD is not edited mid-phase;
|    the divergence is recorded in D-54. There is no enum and no second copy of
|    the category name on services.
|
| 2. slug stays unique across soft-deleted rows. This is the posts/events rule
|    (D-45), not the locations rule (D-40): the public route binds {service:slug},
|    so two rows answering one slug makes the binding ambiguous. There is no
|    generated active_marker column, and Rule::unique() is written without
|    ->withoutTrashed().
|
| 3. is_active is the only publication flag (D-54). PRD 9.8 and roadmap 6B do not
|    define is_published for services; the 6A modules do, but the phase document
|    governs here.
|
| 4. ON DELETE RESTRICT from services to service_categories, so a category in use
|    can never leave a service pointing at nothing (roadmap 6.1/6B-102). The three
|    child tables go the other way — CASCADE — because they have no meaning without
|    their service (PRD 9.1).
|
| @see docs/DECISIONS.md D-54
| @see docs/PRD.md 7.6, 9.8, SVC-01..SVC-06
| @see docs/roadmap/phase-6B-parish-service.md section 6
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            // A key from config/service-icons.php, never arbitrary markup. The
            // form request refuses anything outside that allowlist.
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            // Categories are deactivated rather than deleted while in use.
            // Deleting is soft, and ServiceCategoryController::destroy refuses it
            // outright while a service still points here. See D-54.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sort_order');
            $table->index('is_active');
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            // Unique across soft-deleted rows on purpose — see the header.
            $table->string('slug', 255)->unique();

            // Roadmap 6.1/6B-102: a category in use may not be deleted. RESTRICT
            // rather than CASCADE, because a service whose category vanished has
            // nowhere to be grouped and no page to appear on.
            $table->foreignId('service_category_id')->constrained()->restrictOnDelete();

            $table->string('icon', 100)->nullable();
            $table->text('summary')->nullable();
            // Rich text, sanitized on the way in (HtmlSanitizer, config/html.php).
            $table->longText('introduction')->nullable();
            $table->text('schedule_info')->nullable();
            $table->text('fee_info')->nullable();
            $table->string('contact_name', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();

            // Optional Google Form (SVC-05). NULL means the service does not use
            // one, which is a valid state, not an error.
            $table->string('form_url', 2048)->nullable();
            $table->string('form_button_label', 100)->default('Daftar Sekarang');
            // Only meaningful when form_url is filled and the service is not
            // emergency; the form request normalizes both away otherwise.
            $table->boolean('is_form_open')->default(false);
            $table->text('form_closed_message')->nullable();
            // Emergency services use a direct contact channel, never a form, and
            // cannot be activated without contact_phone (SVC-06).
            $table->boolean('is_emergency')->default(false);
            // The one publication flag (D-54). New services always start inactive
            // until the parish validates their content (SVC-01).
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sort_order');
            $table->index('is_active');
        });

        Schema::create('service_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('text', 255);
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Every read of a service's requirements filters by parent and orders
            // by position, so the pair is the index.
            $table->index(['service_id', 'sort_order']);
        });

        Schema::create('service_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_id', 'sort_order']);
        });

        Schema::create('service_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('question', 255);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        // Reverse dependency order: the three child tables first, then services,
        // then the master categories.
        Schema::dropIfExists('service_faqs');
        Schema::dropIfExists('service_steps');
        Schema::dropIfExists('service_requirements');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
    }
};
