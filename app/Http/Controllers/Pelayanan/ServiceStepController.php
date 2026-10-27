<?php

namespace App\Http\Controllers\Pelayanan;

use App\Actions\Pelayanan\ReorderServiceItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelayanan\ReorderServiceItemsRequest;
use App\Http\Requests\Pelayanan\StoreServiceStepRequest;
use App\Http\Requests\Pelayanan\UpdateServiceStepRequest;
use App\Models\Service;
use App\Models\ServiceStep;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ServiceStepController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(StoreServiceStepRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $data['service_id'] = $service->getKey();
        $data['sort_order'] = $data['sort_order']
            ?? ((int) $service->steps()->max('sort_order')) + 1;

        DB::transaction(function () use ($data, $service): void {
            $item = ServiceStep::query()->create($data);
            $service->touch();
            $this->audit->created('service_steps', $item);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.step_created')]);

        return to_route('services.edit', ['service' => $service, 'tab' => 'prosedur']);
    }

    public function update(
        UpdateServiceStepRequest $request,
        Service $service,
        ServiceStep $step,
    ): RedirectResponse {
        $this->ensureBelongsTo($service, $step);

        DB::transaction(function () use ($request, $step, $service): void {
            $before = $this->audit->snapshot($step, 'service_steps');
            $step->update($request->validated());
            $service->touch();
            $this->audit->updated('service_steps', $step, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.step_updated')]);

        return back();
    }

    public function destroy(Service $service, ServiceStep $step): RedirectResponse
    {
        Gate::authorize('update', $service);
        $this->ensureBelongsTo($service, $step);

        DB::transaction(function () use ($step, $service): void {
            $before = $this->audit->snapshot($step, 'service_steps');
            $step->delete();
            $service->touch();
            $this->audit->deleted('service_steps', $step, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.step_deleted')]);

        return back();
    }

    public function reorder(
        ReorderServiceItemsRequest $request,
        Service $service,
        ReorderServiceItemsAction $action,
    ): RedirectResponse {
        $ids = $request->validated('ids');
        $action->handle($service, ServiceStep::class, $ids);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.step_reordered')]);

        return back();
    }

    private function ensureBelongsTo(Service $service, ServiceStep $step): void
    {
        abort_unless($step->service_id === $service->getKey(), 404);
    }
}
