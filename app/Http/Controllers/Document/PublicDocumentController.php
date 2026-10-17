<?php

namespace App\Http\Controllers\Document;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Document\PublicDocumentCardResource;
use App\Http\Resources\Public\Document\PublicDocumentCategoryResource;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\DocumentStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PublicDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();
        $categorySlug = $request->string('kategori')->toString();

        $category = $categorySlug === ''
            ? null
            : DocumentCategory::query()
                ->active()
                ->where('slug', $categorySlug)
                ->first();

        if ($categorySlug !== '' && $category === null) {
            $categorySlug = '';
        }

        $isFiltered = $search !== '' || $category !== null;

        $documents = Document::query()
            ->published()
            ->with('category')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('documents.title', 'like', "%{$search}%")
                    ->orWhere('documents.description', 'like', "%{$search}%");
            }))
            ->when($category !== null, fn (Builder $query) => $query
                ->where('documents.document_category_id', $category->getKey()))
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        $featured = $isFiltered
            ? collect()
            : Document::query()
                ->published()
                ->featured()
                ->with('category')
                ->ordered()
                ->limit(3)
                ->get();

        $categories = DocumentCategory::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'slug']);

        return Inertia::render('public/dokumen', [
            'documents' => PublicDocumentCardResource::collection($documents),
            'featured' => array_values(PublicDocumentCardResource::collection($featured)->resolve()),
            'categories' => array_values(PublicDocumentCategoryResource::collection($categories)->resolve()),
            'filters' => [
                'q' => $search,
                'kategori' => $categorySlug,
            ],
        ]);
    }

    public function download(
        Request $request,
        Document $document,
        DocumentStorage $storage,
    ): StreamedResponse {
        abort_unless(
            $document->status === DocumentStatus::Published && $document->published_at !== null,
            404,
        );

        if (! $storage->exists($document)) {
            Log::warning('Berkas dokumen tidak ditemukan saat diunduh.', [
                'document_id' => $document->getKey(),
            ]);

            abort(404);
        }

        $inline = $request->boolean('inline');
        $filename = $storage->downloadFilename($document);

        if ($inline) {
            abort_unless($document->isPdf(), 404);

            return Storage::disk($document->file_disk)->response(
                $document->file_path,
                $filename,
                [
                    'Content-Type' => 'application/pdf',
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => 'private, no-store',
                ],
                'inline',
            );
        }

        try {
            DB::table('documents')
                ->where('id', $document->getKey())
                ->increment('download_count');
        } catch (Throwable $exception) {
            Log::warning('Penghitung unduhan gagal diperbarui.', [
                'document_id' => $document->getKey(),
                'message' => $exception->getMessage(),
            ]);
        }

        return Storage::disk($document->file_disk)->download(
            $document->file_path,
            $filename,
            [
                'Content-Type' => $document->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }
}
