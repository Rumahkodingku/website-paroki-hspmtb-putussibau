<?php

namespace App\Http\Controllers\Backup;

use App\Enums\BackupType;
use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\AuditLogger;
use App\Services\Backup\BackupStorage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    public function download(
        Backup $backup,
        BackupStorage $storage,
        AuditLogger $audit,
    ): StreamedResponse {
        Gate::authorize('download', $backup);

        if ($backup->path === null) {
            abort(404, __('backups.errors.file_missing'));
        }

        $path = $backup->path;

        try {
            $exists = Storage::disk($backup->disk)->exists($path);
            $stream = $exists
                ? Storage::disk($backup->disk)->readStream($path)
                : null;
        } catch (\Throwable) {
            abort(503, __('backups.errors.storage_unavailable'));
        }

        if ($stream === null || ! is_resource($stream)) {
            $backup->forceFill(['file_missing_at' => now()])->save();

            abort(404, __('backups.errors.file_missing'));
        }

        $audit->recordModel(
            'backups',
            'backup_downloaded',
            'success',
            $backup,
            [],
            [],
            ['backup_id' => $backup->uuid, 'type' => $backup->type->value],
            $backup->type->label(),
        );

        $name = $storage->downloadFilename(
            $backup->original_filename,
            $backup->type,
            $backup->created_at,
        );

        $headers = [
            'Content-Type' => $backup->type === BackupType::Database
                ? 'application/gzip'
                : 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $name, $headers);
    }
}
