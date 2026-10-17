<?php

namespace App\Actions\Document;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Writes a document and its file together.
 *
 * The new file is stored before the database write. If the transaction fails,
 * the new file is deleted; the old file is only deleted after the row has been
 * updated, so a failed replace never loses the working file.
 */
class SaveDocumentAction
{
    public function __construct(private readonly DocumentStorage $storage) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(
        Document $document,
        array $attributes,
        ?UploadedFile $file,
        User $actor,
    ): Document {
        $newFile = $file === null ? null : $this->storage->store($file);
        $oldFile = $document->exists
            ? ['disk' => $document->file_disk, 'path' => $document->file_path]
            : null;

        try {
            $saved = DB::transaction(function () use ($document, $attributes, $newFile, $actor): Document {
                if ($newFile !== null) {
                    $attributes = array_merge($attributes, $newFile);
                }

                if (! $document->exists) {
                    $attributes['uploaded_by'] = $actor->getKey();
                }

                $attributes['updated_by'] = $actor->getKey();
                $attributes['published_at'] = $this->resolvePublishedAt($document, $attributes);

                $document->fill($attributes);
                $document->save();

                return $document;
            });
        } catch (Throwable $exception) {
            if ($newFile !== null) {
                $this->storage->delete($newFile['file_disk'], $newFile['file_path']);
            }

            throw $exception;
        }

        if ($newFile !== null && $oldFile !== null && $oldFile['path'] !== $newFile['file_path']) {
            $this->deleteIfUnreferenced($document, $oldFile['disk'], $oldFile['path']);
        }

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolvePublishedAt(Document $document, array $attributes): mixed
    {
        $willPublish = ($attributes['status'] ?? null) === DocumentStatus::Published->value;

        if ($willPublish && $document->published_at === null) {
            return now();
        }

        return $document->published_at;
    }

    private function deleteIfUnreferenced(Document $document, string $disk, string $path): void
    {
        $referenced = Document::query()
            ->withTrashed()
            ->where('file_disk', $disk)
            ->where('file_path', $path)
            ->whereKeyNot($document->getKey())
            ->exists();

        if (! $referenced) {
            $this->storage->delete($disk, $path);
        }
    }
}
