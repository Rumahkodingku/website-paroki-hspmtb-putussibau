<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * One pastoral service: a sacrament or a service the parish offers.
 *
 * Three things about this model are load-bearing and easy to undo by accident.
 *
 * 1. is_active is the only publication flag (SVC-01, D-54). A new service is
 *    always inactive; activation is an explicit act, because the requirements
 *    and procedure are pastoral policy that the parish validates first.
 *
 * 2. The public site is one scope, not an exclusion. scopeActive is what the
 *    list, the detail binding and the "other services" strip all share, so an
 *    inactive or soft-deleted service answers 404 (XC-E3) everywhere rather than
 *    in whichever query remembered to check.
 *
 * 3. introduction is sanitized on the way in by the form request and only ever
 *    rendered through components/rich-text.tsx. The allowlist is config/html.php
 *    (D-25).
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $service_category_id
 * @property int|null $thumbnail_media_id
 * @property string|null $thumbnail_alt
 * @property string|null $summary
 * @property string|null $introduction
 * @property string|null $audience_info
 * @property string|null $preparation_info
 * @property string|null $schedule_info
 * @property string|null $fee_info
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $form_url
 * @property string $form_button_label
 * @property bool $is_form_open
 * @property string|null $form_closed_message
 * @property bool $is_emergency
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ServiceCategory $serviceCategory
 * @property-read Media|null $thumbnailMedia
 * @property-read Collection<int, ServiceRequirement> $requirements
 * @property-read Collection<int, ServiceStep> $steps
 * @property-read Collection<int, ServiceFaq> $faqs
 */
#[Fillable([
    'title',
    'slug',
    'service_category_id',
    'thumbnail_media_id',
    'thumbnail_alt',
    'summary',
    'introduction',
    'audience_info',
    'preparation_info',
    'schedule_info',
    'fee_info',
    'contact_name',
    'contact_phone',
    'form_url',
    'form_button_label',
    'is_form_open',
    'form_closed_message',
    'is_emergency',
    'is_active',
    'sort_order',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * The uploaded image shown on the service card and detail page.
     *
     * Nullable, SET NULL: a service without a thumbnail is valid, and removing
     * the media row must not remove the service.
     *
     * @return BelongsTo<Media, $this>
     */
    public function thumbnailMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }

    /**
     * The documents a visitor may download from this service.
     *
     * Only published documents are loaded by the public controller; the pivot
     * itself may still hold a document that was later archived.
     *
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }

    /**
     * @return HasMany<ServiceRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(ServiceRequirement::class);
    }

    /**
     * @return HasMany<ServiceStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ServiceStep::class);
    }

    /**
     * @return HasMany<ServiceFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(ServiceFaq::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_form_open' => 'boolean',
            'is_emergency' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Only what a visitor may see (SVC-01, XC-E3).
     *
     * Soft-deleted rows are already excluded by SoftDeletes, so this is the
     * publication flag alone.
     *
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The stable ordering: sort_order, then id.
     *
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Bind `{service:slug}` on the public route to an active service only.
     *
     * The admin routes bind by id and must reach inactive services, so only the
     * slug-bound public route gets this. An inactive or soft-deleted slug
     * therefore returns null and the route 404s (XC-E3, SVC-08).
     *
     * @param  string|int  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->active()->where('slug', $value)->first();
    }

    /**
     * Whether the service has a Google Form link at all.
     */
    public function hasFormUrl(): bool
    {
        return $this->form_url !== null && trim($this->form_url) !== '';
    }

    /**
     * Whether the public page should offer a Google Form for this service.
     *
     * Emergency services never use a form (SVC-06), even if a URL was once
     * saved; the form request clears it on save, and this guards the read path
     * as well so an imported row cannot leak one.
     */
    public function usesGoogleForm(): bool
    {
        return $this->hasFormUrl() && ! $this->is_emergency;
    }

    /**
     * Whether the registration button is live.
     *
     * SVC-05: open means a link exists, the service is not emergency, and the
     * admin has left registration open. is_form_open without a URL is a state the
     * form request normalizes away, but this checks both so it cannot be relied
     * on by accident.
     */
    public function isAcceptingRegistrations(): bool
    {
        return $this->usesGoogleForm() && $this->is_form_open;
    }

    /**
     * Whether this service carries its own contact number.
     */
    public function hasOwnContact(): bool
    {
        return $this->contact_phone !== null && trim($this->contact_phone) !== '';
    }
}
