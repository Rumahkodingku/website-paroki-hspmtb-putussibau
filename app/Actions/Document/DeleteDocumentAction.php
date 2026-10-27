<?php

namespace App\Actions\Document;

use App\Models\Document;
use App\Services\AuditLogger;
use App\Services\DocumentStorage;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Soft-deletes a document, detaches its services, and removes the file.
 *
 * There is no restore screen, so keeping a 10 MB file behind a hidden row would
 * only accumulate storage. The row remains for audit/history.
 */
class DeleteDocumentAction
{
    public function __construct(
        private readonly DocumentStorage $storage,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Document $document): void
    {
        $disk = $document->file_disk;
        $path = $document->file_path;

        DB::transaction(function () use ($document): void {
            $before = $this->audit->snapshot($document, 'documents');

            $document->services()->detach();
            $document->delete();

            $this->audit->deleted('documents', $document, $before);
        });

        try {
            $this->storage->delete($disk, $path);
        } catch (Throwable $exception) {
            $this->audit->system(
                'documents',
                'storage_delete_failed',
                'failure',
                __('audit.descriptions.storage_delete_failed'),
                ['reason_code' => $exception::class],
                Document::class,
                (string) $document->getKey(),
                $document->title,
            );

            throw $exception;
        }
    }
}
