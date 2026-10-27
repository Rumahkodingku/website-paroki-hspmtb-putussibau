<?php

namespace App\Http\Controllers\MasterData;

use App\Concerns\ServiceFormMeta;
use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelayanan\StoreServiceCategoryRequest;
use App\Http\Requests\Pelayanan\UpdateServiceCategoryRequest;
use App\Http\Resources\Pelayanan\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    use ServiceFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ServiceCategory::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $categories = ServiceCategory::query()
            ->withCount('services')
            ->when($search !== '', fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%"))
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('is_active', $status === 'active')
            );

        $categories = $this->applyAdminSort(
            $categories,
            $request,
            [
                'name' => 'service_categories.name',
                'sort_order' => 'service_categories.sort_order',
                'is_active' => 'service_categories.is_active',
            ],
            [['service_categories.sort_order', 'asc']],
        )->paginate($this->perPage($request))->withQueryString();

        return Inertia::render('admin/master-data/kategori-pelayanan/index', [
            'categories' => ServiceCategoryResource::collection($categories),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->serviceFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ServiceCategory::class);

        return Inertia::render('admin/master-data/kategori-pelayanan/tambah', [
            ...$this->serviceFormMeta(),
        ]);
    }

    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $serviceCategory = ServiceCategory::query()->create($request->validated());
            $this->audit->created('service_categories', $serviceCategory);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori pelayanan berhasil ditambahkan.')]);

        return to_route('service-categories.index');
    }

    public function edit(ServiceCategory $serviceCategory): Response
    {
        Gate::authorize('update', $serviceCategory);

        return Inertia::render('admin/master-data/kategori-pelayanan/ubah', [
            'category' => ServiceCategoryResource::make($serviceCategory)->resolve(),
            ...$this->serviceFormMeta(),
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        DB::transaction(function () use ($request, $serviceCategory): void {
            $before = $this->audit->snapshot($serviceCategory, 'service_categories');
            $wasActive = $serviceCategory->is_active;

            $serviceCategory->update($request->validated());

            $action = $wasActive === $serviceCategory->is_active
                ? 'updated'
                : ($serviceCategory->is_active ? 'activated' : 'deactivated');

            $this->audit->updated('service_categories', $serviceCategory, $before, $action);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori pelayanan berhasil diperbarui.')]);

        return to_route('service-categories.index');
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        Gate::authorize('delete', $serviceCategory);

        $services = $serviceCategory->services()->withTrashed()->count();

        if ($services > 0) {
            return to_route('service-categories.index')->withErrors([
                'category' => __('Kategori tidak dapat dihapus karena masih dipakai :count layanan. Pindahkan layanan tersebut atau nonaktifkan kategorinya.', [
                    'count' => $services,
                ]),
            ]);
        }

        DB::transaction(function () use ($serviceCategory): void {
            $before = $this->audit->snapshot($serviceCategory, 'service_categories');
            $serviceCategory->delete();
            $this->audit->deleted('service_categories', $serviceCategory, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori pelayanan berhasil dihapus.')]);

        return to_route('service-categories.index');
    }
}
