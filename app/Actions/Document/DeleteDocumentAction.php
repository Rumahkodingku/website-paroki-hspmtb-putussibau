<?php

namespace App\Actions\Document;

use App\Models\Document;
use App\Services\DocumentStorage;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deletes a document, detaches its services, and removes the file.
 *
 * There is no restore screen, so keeping a 10 MB file behind a hidden row would
 * only accumulate storage. The row remains for audit/history.
 */
class DeleteDocumentAction
{
    public function __construct(private readonly DocumentStorage $storage) {}

    public function handle(Document $document): void
    {
        $disk = $document->file_disk;
        $path = $document->file_path;

        DB::transaction(function () use ($document): void {
            $document->services()->detach();
            $document->delete();
        });

        $this->storage->delete($disk, $path);
    }
}
