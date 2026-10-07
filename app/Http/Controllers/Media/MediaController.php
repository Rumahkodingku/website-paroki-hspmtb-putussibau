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
            $this->filenameFor($upload->getClientOriginalName()),
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
     * The client name is never used as a path. It keeps its extension for
     * convenience, but that extension is only ever read from bytes that already
     * passed the MIME rule, so it cannot be used to smuggle a .php onto the
     * private disk.
     */
    private function filenameFor(string $clientName): string
    {
        return Str::uuid()->toString().'.'.strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
    }
}
