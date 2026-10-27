<?php

namespace App\Http\Controllers\Document;

use App\Actions\Document\DeleteDocumentAction;
use App\Actions\Document\SaveDocumentAction;
use App\Concerns\DocumentFormMeta;
use App\Concerns\SortsAdminList;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\UpdateDocumentRequest;
use App\Http\Resources\Document\DocumentListResource;
use App\Http\Resources\Document\DocumentResource;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\DocumentStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use DocumentFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Document::class);

        $search = $request->string('search')->toString();
        $categoryId = $request->string('kategori')->toString();
        $status = $request->string('status')->toString();

        $documents = Document::query()
            ->with('category')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('documents.title', 'like', "%{$search}%"))
            ->when(
                $categoryId !== '' && ctype_digit($categoryId),
                fn (Builder $query) => $query->where('documents.document_category_id', (int) $categoryId)
            )
            ->when(
                in_array($status, DocumentStatus::values(), true),
                fn (Builder $query) => $query->where('documents.status', $status)
            );

        $documents = $this->applyAdminSort(
            $documents,
            $request,
            [
                'title' => 'documents.title',
                'status' => 'documents.status',
                'document_date' => 'documents.document_date',
                'published_at' => 'documents.published_at',
                'download_count' => 'documents.download_count',
                'updated_at' => 'documents.updated_at',
            ],
            [['documents.updated_at', 'desc']],
        )->paginate($this->perPage($request))->withQueryString();

        return Inertia::render('admin/dokumen', [
            'documents' => DocumentListResource::collection($documents),
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'kategori' => $categoryId,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->documentFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Document::class);

        return Inertia::render('admin/dokumen/tambah', [
            'categories' => $this->categoryOptions(),
            ...$this->documentFormMeta(),
        ]);
    }

    public function store(StoreDocumentRequest $request, SaveDocumentAction $action): RedirectResponse
    {
        $document = $action->handle(
            new Document,
            $request->documentPayload(),
            $request->file('file'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.flash.created')]);

        return to_route('documents.edit', $document);
    }

    public function edit(Document $document): Response
    {
        Gate::authorize('update', $document);

        $document->load('category');

        return Inertia::render('admin/dokumen/ubah', [
            'document' => DocumentResource::make($document)->resolve(),
            'categories' => $this->categoryOptions($document),
            ...$this->documentFormMeta(),
        ]);
    }

    public function update(UpdateDocumentRequest $request, Document $document, SaveDocumentAction $action): RedirectResponse
    {
        $action->handle(
            $document,
            $request->documentPayload(),
            $request->file('file'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.flash.updated')]);

        return to_route('documents.edit', $document);
    }

    public function destroy(Document $document, DeleteDocumentAction $action): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $action->handle($document);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.flash.deleted')]);

        return to_route('documents.index');
    }

    public function preview(Document $document, DocumentStorage $storage): StreamedResponse
    {
        Gate::authorize('view', $document);

        abort_unless($document->isPdf(), 404);
        abort_unless($storage->exists($document), 404);

        return Storage::disk($document->file_disk)->response(
            $document->file_path,
            $storage->downloadFilename($document),
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
            'inline',
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function categoryOptions(?Document $current = null): array
    {
        $query = DocumentCategory::query()->where('is_active', true);

        if ($current !== null) {
            $query->orWhereKey($current->document_category_id);
        }

        return array_values(
            $query->ordered()
                ->get(['id', 'name'])
                ->map(fn (DocumentCategory $category): array => [
                    'id' => $category->getKey(),
                    'name' => $category->name,
                ])
                ->all()
        );
    }
}
