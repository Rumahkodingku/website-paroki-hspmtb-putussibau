<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * One downloadable document and its active file.
 *
 * Files live on a private disk and are never exposed through a URL. The public
 * route resolves this row, checks `status`, and streams the file through the
 * controller. `download_count` is written only by that controller.
 *
 * @property int $id
 * @property int $document_category_id
 * @property string $title
 * @property string|null $description
 * @property string $file_disk
 * @property string $file_path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $version_label
 * @property Carbon|null $document_date
 * @property Carbon|null $expires_at
 * @property DocumentStatus $status
 * @property bool $is_featured
 * @property int $download_count
 * @property Carbon|null $published_at
 * @property int|null $uploaded_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read DocumentCategory $category
 * @property-read User|null $uploadedBy
 * @property-read User|null $updatedBy
 * @property-read Collection<int, Service> $services
 */
#[Fillable([
    'document_category_id',
    'title',
    'description',
    'file_disk',
    'file_path',
    'original_filename',
    'mime_type',
    'file_size',
    'version_label',
    'document_date',
    'expires_at',
    'status',
    'is_featured',
    'published_at',
    'uploaded_by',
    'updated_by',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsTo<DocumentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'is_featured' => 'boolean',
            'file_size' => 'integer',
            'download_count' => 'integer',
            'document_date' => 'date',
            'expires_at' => 'date',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', DocumentStatus::Published)
            ->whereNotNull('published_at');
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('published_at')
            ->orderByRaw('document_date is null')
            ->orderByDesc('document_date')
            ->orderByDesc('id');
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->lt(today(config('app.display_timezone')));
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function extension(): string
    {
        /** @var array<string, string> $extensions */
        $extensions = (array) config('documents.extensions');

        return $extensions[$this->mime_type] ?? 'bin';
    }
}
