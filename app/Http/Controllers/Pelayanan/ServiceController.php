<?php

namespace App\Http\Controllers\Pelayanan;

use App\Actions\Pelayanan\SaveServiceAction;
use App\Concerns\ServiceFormMeta;
use App\Concerns\SortsAdminList;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelayanan\StoreServiceRequest;
use App\Http\Requests\Pelayanan\UpdateServiceRequest;
use App\Http\Resources\Pelayanan\ServiceFaqResource;
use App\Http\Resources\Pelayanan\ServiceListResource;
use App\Http\Resources\Pelayanan\ServiceOptionResource;
use App\Http\Resources\Pelayanan\ServiceRequirementResource;
use App\Http\Resources\Pelayanan\ServiceResource;
use App\Http\Resources\Pelayanan\ServiceStepResource;
use App\Models\Document;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use ServiceFormMeta;
    use SortsAdminList;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Service::class);

        $search = $request->string('search')->toString();
        $category = $request->string('kategori')->toString();
        $status = $request->string('status')->toString();

        $services = Service::query()
            ->with(['serviceCategory', 'thumbnailMedia.variants'])
            ->when($search !== '', fn (Builder $query) => $query
                ->where('services.title', 'like', "%{$search}%"))
            ->when(
                $category !== '' && ctype_digit($category),
                fn (Builder $query) => $query->where('services.service_category_id', (int) $category)
            )
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('services.is_active', $status === 'active')
            );

        $services = $this->applyAdminSort(
            $services,
            $request,
            [
                'title' => 'services.title',
                'slug' => 'services.slug',
                'sort_order' => 'services.sort_order',
                'is_active' => 'services.is_active',
                'updated_at' => 'services.updated_at',
            ],
            [['services.sort_order', 'asc']],
        )->paginate(15)->withQueryString();

        return Inertia::render('admin/pelayanan', [
            'services' => ServiceListResource::collection($services),
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
                'sort' => $request->string('sort')->toString(),
                'direction' => $request->string('direction')->toString(),
            ],
            ...$this->serviceFormMeta(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Service::class);

        return Inertia::render('admin/pelayanan/tambah', [
            'categories' => $this->categoryOptions(),
            'documents' => $this->documentOptions(),
            ...$this->serviceFormMeta(),
        ]);
    }

    public function store(StoreServiceRequest $request, SaveServiceAction $action): RedirectResponse
    {
        $service = $action->handle(new Service, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.service_created')]);

        return to_route('services.edit', $service);
    }

    public function edit(Service $service): Response
    {
        Gate::authorize('update', $service);

        $service->load([
            'serviceCategory',
            'thumbnailMedia.variants',
            'requirements' => fn ($query) => $query->ordered(),
            'steps' => fn ($query) => $query->ordered(),
            'faqs' => fn ($query) => $query->ordered(),
            'documents',
        ]);

        return Inertia::render('admin/pelayanan/ubah', [
            'service' => ServiceResource::make($service)->resolve(),
            'requirements' => array_values(ServiceRequirementResource::collection($service->requirements)->resolve()),
            'steps' => array_values(ServiceStepResource::collection($service->steps)->resolve()),
            'faqs' => array_values(ServiceFaqResource::collection($service->faqs)->resolve()),
            'categories' => $this->categoryOptions($service),
            'documents' => $this->documentOptions($service),
            ...$this->serviceFormMeta(),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service, SaveServiceAction $action): RedirectResponse
    {
        $action->handle($service, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.service_updated')]);

        return to_route('services.edit', $service);
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.service_deleted')]);

        return to_route('services.index');
    }

    /**
     * Published documents, plus anything already attached to this service so an
     * archived link can be seen and removed.
     *
     * @return list<array{id: int, title: string, status: string, status_label: string}>
     */
    private function documentOptions(?Service $current = null): array
    {
        $query = Document::query()->where('status', DocumentStatus::Published);

        if ($current !== null) {
            $query->orWhereIn('id', $current->documents()->pluck('documents.id'));
        }

        return array_values(
            $query->orderBy('title')
                ->get(['id', 'title', 'status'])
                ->map(fn (Document $document): array => [
                    'id' => $document->getKey(),
                    'title' => $document->title,
                    'status' => $document->status->value,
                    'status_label' => $document->status->label(),
                ])
                ->all()
        );
    }

    /**
     * The category dropdown: active categories, plus the one a service already
     * sits in even if it has since been deactivated, so editing that service
     * does not silently move it.
     *
     * @return list<array<string, mixed>>
     */
    private function categoryOptions(?Service $current = null): array
    {
        $categories = ServiceCategory::query()->where('is_active', true);

        if ($current !== null) {
            $categories->orWhere('id', $current->service_category_id);
        }

        return array_values(
            ServiceOptionResource::collection($categories->ordered()->get())->resolve()
        );
    }
}
