<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Livestream\StoreLivestreamCategoryRequest;
use App\Http\Requests\Livestream\UpdateLivestreamCategoryRequest;
use App\Http\Resources\Livestream\LivestreamCategoryResource;
use App\Models\LivestreamCategory;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LivestreamCategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LivestreamCategory::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $categories = LivestreamCategory::query()
            ->withCount('livestreams')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('is_active', $status === 'active')
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return Inertia::render('admin/master-data/kategori-livestreaming/index', [
            'categories' => LivestreamCategoryResource::collection($categories),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', LivestreamCategory::class);

        return Inertia::render('admin/master-data/kategori-livestreaming/tambah');
    }

    public function store(StoreLivestreamCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $category = LivestreamCategory::query()->create($request->validated());
            $this->audit->created('livestream_categories', $category);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori livestreaming berhasil dibuat.']);

        return to_route('livestream-categories.index');
    }

    public function edit(LivestreamCategory $livestreamCategory): Response
    {
        Gate::authorize('update', $livestreamCategory);

        return Inertia::render('admin/master-data/kategori-livestreaming/ubah', [
            'category' => LivestreamCategoryResource::make($livestreamCategory)->resolve(),
        ]);
    }

    public function update(UpdateLivestreamCategoryRequest $request, LivestreamCategory $livestreamCategory): RedirectResponse
    {
        DB::transaction(function () use ($request, $livestreamCategory): void {
            $before = $this->audit->snapshot($livestreamCategory, 'livestream_categories');

            $livestreamCategory->update($request->validated());

            $this->audit->updated('livestream_categories', $livestreamCategory, $before, 'updated');
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori livestreaming berhasil diperbarui.']);

        return to_route('livestream-categories.index');
    }

    public function destroy(LivestreamCategory $livestreamCategory): RedirectResponse
    {
        Gate::authorize('delete', $livestreamCategory);

        if ($livestreamCategory->livestreams()->exists()) {
            return back()->withErrors(['category' => 'Kategori masih digunakan oleh livestreaming.']);
        }

        DB::transaction(function () use ($livestreamCategory): void {
            $before = $this->audit->snapshot($livestreamCategory, 'livestream_categories');
            $livestreamCategory->delete();
            $this->audit->deleted('livestream_categories', $livestreamCategory, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori livestreaming berhasil dihapus.']);

        return to_route('livestream-categories.index');
    }

    protected function perPage(Request $request): int
    {
        return 15;
    }
}
