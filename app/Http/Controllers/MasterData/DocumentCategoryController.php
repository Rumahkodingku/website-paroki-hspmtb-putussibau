<?php

namespace App\Http\Controllers\MasterData;

use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentCategoryRequest;
use App\Http\Requests\Document\UpdateDocumentCategoryRequest;
use App\Http\Resources\Document\DocumentCategoryResource;
use App\Models\DocumentCategory;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentCategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DocumentCategory::class);

        $search = $request->string('search')->toString();

        $categories = DocumentCategory::query()
            ->withCount(['documents' => fn (Builder $query) => $query
                ->withoutGlobalScope(SoftDeletingScope::class)])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('document_categories.name', 'like', "%{$search}%"));

        $categories = $this->applyAdminSort(
            $categories,
            $request,
            [
                'name' => 'document_categories.name',
                'slug' => 'document_categories.slug',
                'sort_order' => 'document_categories.sort_order',
            ],
            [['document_categories.sort_order', 'asc'], ['document_categories.name', 'asc']],
        )->paginate($this->perPage($request))->withQueryString();

        return Inertia::render('admin/master-data/kategori-dokumen/index', [
            'categories' => DocumentCategoryResource::collection($categories),
            'filters' => [
                'search' => $search,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            'copy' => (array) __('documents.category'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', DocumentCategory::class);

        return Inertia::render('admin/master-data/kategori-dokumen/tambah', [
            'copy' => (array) __('documents.category'),
        ]);
    }

    public function store(StoreDocumentCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $documentCategory = DocumentCategory::query()->create($request->validated());
            $this->audit->created('document_categories', $documentCategory);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.flash.category_created')]);

        return to_route('document-categories.index');
    }

    public function edit(DocumentCategory $documentCategory): Response
    {
        Gate::authorize('update', $documentCategory);

        return Inertia::render('admin/master-data/kategori-dokumen/ubah', [
            'category' => DocumentCategoryResource::make($documentCategory)->resolve(),
            'copy' => (array) __('documents.category'),
        ]);
    }

    public function update(UpdateDocumentCategoryRequest $request, DocumentCategory $documentCategory): RedirectResponse
    {
        DB::transaction(function () use ($request, $documentCategory): void {
            $before = $this->audit->snapshot($documentCategory, 'document_categories');
            $wasActive = $documentCategory->is_active;

            $documentCategory->update($request->validated());

            $action = $wasActive === $documentCategory->is_active
                ? 'updated'
                : ($documentCategory->is_active ? 'activated' : 'deactivated');

            $this->audit->updated('document_categories', $documentCategory, $before, $action);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.flash.category_updated')]);

        return to_route('document-categories.index');
    }

    public function destroy(DocumentCategory $documentCategory): RedirectResponse
    {
        Gate::authorize('delete', $documentCategory);

        $documents = $documentCategory->documents()->withTrashed()->count();

        if ($documents > 0) {
            return to_route('document-categories.index')->withErrors([
                'document_category' => __('documents.flash.category_in_use', ['count' => $documents]),
            ]);
        }

        DB::transaction(function () use ($documentCategory): void {
            $before = $this->audit->snapshot($documentCategory, 'document_categories');
            $documentCategory->delete();
            $this->audit->deleted('document_categories', $documentCategory, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.flash.category_deleted')]);

        return to_route('document-categories.index');
    }
}
