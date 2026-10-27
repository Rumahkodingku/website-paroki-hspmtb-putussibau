<?php

namespace App\Http\Controllers\Pelayanan;

use App\Actions\Pelayanan\ReorderServiceItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelayanan\ReorderServiceItemsRequest;
use App\Http\Requests\Pelayanan\StoreServiceRequirementRequest;
use App\Http\Requests\Pelayanan\UpdateServiceRequirementRequest;
use App\Models\Service;
use App\Models\ServiceRequirement;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ServiceRequirementController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(StoreServiceRequirementRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $data['service_id'] = $service->getKey();
        $data['sort_order'] = $data['sort_order']
            ?? ((int) $service->requirements()->max('sort_order')) + 1;

        DB::transaction(function () use ($data, $service): void {
            $item = ServiceRequirement::query()->create($data);
            $service->touch();
            $this->audit->created('service_requirements', $item);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.requirement_created')]);

        return to_route('services.edit', ['service' => $service, 'tab' => 'persyaratan']);
    }

    public function update(
        UpdateServiceRequirementRequest $request,
        Service $service,
        ServiceRequirement $requirement,
    ): RedirectResponse {
        $this->ensureBelongsTo($service, $requirement);

        DB::transaction(function () use ($request, $requirement, $service): void {
            $before = $this->audit->snapshot($requirement, 'service_requirements');
            $requirement->update($request->validated());
            $service->touch();
            $this->audit->updated('service_requirements', $requirement, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.requirement_updated')]);

        return back();
    }

    public function destroy(Service $service, ServiceRequirement $requirement): RedirectResponse
    {
        Gate::authorize('update', $service);
        $this->ensureBelongsTo($service, $requirement);

        DB::transaction(function () use ($requirement, $service): void {
            $before = $this->audit->snapshot($requirement, 'service_requirements');
            $requirement->delete();
            $service->touch();
            $this->audit->deleted('service_requirements', $requirement, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.requirement_deleted')]);

        return back();
    }

    public function reorder(
        ReorderServiceItemsRequest $request,
        Service $service,
        ReorderServiceItemsAction $action,
    ): RedirectResponse {
        $ids = $request->validated('ids');
        $action->handle($service, ServiceRequirement::class, $ids);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.requirement_reordered')]);

        return back();
    }

    private function ensureBelongsTo(Service $service, ServiceRequirement $requirement): void
    {
        abort_unless($requirement->service_id === $service->getKey(), 404);
    }
}
