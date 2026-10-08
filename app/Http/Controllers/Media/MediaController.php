<?php

namespace App\Http\Controllers\Media;

use App\Enums\MediaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * The media upload endpoint.
 *
 * Phase 01 P10 is a foundation, not a feature. There is no media library page and
 * no picker anywhere in the interface, so this endpoint is currently reachable
 * only by hand. That is deliberate: PRD Fase 1 asks for the pipeline and the job,
 * and the browsing UI arrives with the first module that needs it. See
 * docs/DECISIONS.md D-24.
 *
 * @see docs/DECISIONS.md D-24
 */
class MediaController extends Controller
{
    /**
     * Detected MIME type to stored extension.
     *
     * The list is exactly config('media.mime_types'). Anything else can only
     * reach here after the upload rule passed, so 'bin' is a backstop rather than
     * a reachable branch.
     *
     * @var array<string, string>
     */
    private const EXTENSION_BY_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Accept an upload and hand it to the queue.
     *
     * XC-M1: processing happens in a job, not here. Decoding and resizing three
     * sizes inside the request is what makes an upload time out, and a timed-out
     * upload tells the uploader nothing about whether it worked.
     */
    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $upload = $request->file('file');

        $path = $upload->storeAs(
            (string) config('media.paths.original'),
            $this->filenameFor($upload),
            (string) config('media.original_disk'),
        );

        $media = Media::query()->create([
            'disk' => (string) config('media.original_disk'),
            'path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            // The provisional value is replaced by inspect() in the job, which
            // reads the type from the file itself rather than from the browser.
            'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
            'file_size' => $upload->getSize(),
            'alt_text' => $request->input('alt_text'),
            'status' => MediaStatus::Pending,
            'uploader_id' => $request->user()->id,
        ]);

        // Dispatched after the row exists, and outside any transaction, so a
        // worker can never pick the job up before the commit it depends on.
        GenerateImageVariants::dispatch($media->getKey());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Gambar diterima dan sedang diproses.'),
        ]);

        return back();
    }

    /**
     * The processing state of one upload, as JSON.
     *
     * XC-M1 asks the admin UI to display it. There is no UI in P10, but the thing
     * such a UI polls has to exist before one can be built against it, and JSON
     * is the right shape for that.
     */
    public function show(Request $request, Media $media): JsonResponse
    {
        abort_unless(
            $request->user()->can('media.view'),
            403,
        );

        return response()->json([
            'media' => [
                'id' => $media->getKey(),
                'status' => $media->status->value,
                'error_message' => $media->error_message,
                'alt_text' => $media->alt_text,
                'width' => $media->width,
                'height' => $media->height,
                'variants' => $media->variants()
                    ->get(['name', 'width', 'height', 'file_size'])
                    ->map(fn ($variant): array => [
                        'name' => $variant->name,
                        'width' => $variant->width,
                        'height' => $variant->height,
                        'file_size' => $variant->file_size,
                        'url' => $media->url($variant->name),
                    ])
                    ->all(),
            ],
        ]);
    }

    /**
     * Remove an upload and everything derived from it.
     *
     * The unique index cascades the variant rows; the files themselves do not
     * cascade, so they are deleted explicitly rather than being left orphaned on
     * disk.
     */
    public function destroy(Request $request, Media $media): RedirectResponse
    {
        abort_unless(
            $request->user()->can('media.delete'),
            403,
        );

        $variantDisk = Storage::disk((string) config('media.variant_disk'));

        foreach ($media->variants as $variant) {
            $variantDisk->delete($variant->path);
        }

        Storage::disk($media->disk)->delete($media->path);

        $media->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Gambar dihapus.'),
        ]);

        return back();
    }

    /**
     * A stored filename that cannot be guessed and cannot execute.
     *
     * The extension comes from the detected MIME type, never from the client.
     * The original name is kept on the row for display, but using it for the path
     * would let a crafted upload land as something.php on disk, and there is no
     * reason for the extension to be the only part of that decision we do not
     * take from the bytes.
     */
    private function filenameFor(UploadedFile $upload): string
    {
        $detected = $upload->getMimeType();

        $extension = self::EXTENSION_BY_MIME[$detected] ?? 'bin';

        return Str::uuid()->toString().'.'.$extension;
    }
}
