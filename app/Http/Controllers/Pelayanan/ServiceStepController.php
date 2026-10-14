<?php

namespace App\Http\Controllers\Pelayanan;

use App\Actions\Pelayanan\ReorderServiceItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelayanan\ReorderServiceItemsRequest;
use App\Http\Requests\Pelayanan\StoreServiceStepRequest;
use App\Http\Requests\Pelayanan\UpdateServiceStepRequest;
use App\Models\Service;
use App\Models\ServiceStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ServiceStepController extends Controller
{
    public function store(StoreServiceStepRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $data['service_id'] = $service->getKey();
        $data['sort_order'] = $data['sort_order']
            ?? ((int) $service->steps()->max('sort_order')) + 1;

        ServiceStep::create($data);
        $service->touch();

        return to_route('services.edit', ['service' => $service, 'tab' => 'prosedur'])
            ->with('success', __('pelayanan.flash.step_created'));
    }

    public function update(
        UpdateServiceStepRequest $request,
        Service $service,
        ServiceStep $step,
    ): RedirectResponse {
        $this->ensureBelongsTo($service, $step);

        $step->update($request->validated());
        $service->touch();

        return back()->with('success', __('pelayanan.flash.step_updated'));
    }

    public function destroy(Service $service, ServiceStep $step): RedirectResponse
    {
        Gate::authorize('update', $service);
        $this->ensureBelongsTo($service, $step);

        $step->delete();
        $service->touch();

        return back()->with('success', __('pelayanan.flash.step_deleted'));
    }

    public function reorder(
        ReorderServiceItemsRequest $request,
        Service $service,
        ReorderServiceItemsAction $action,
    ): RedirectResponse {
        $ids = $request->validated('ids');
        $action->handle($service, ServiceStep::class, $ids);

        return back()->with('success', __('pelayanan.flash.step_reordered'));
    }

    private function ensureBelongsTo(Service $service, ServiceStep $step): void
    {
        abort_unless($step->service_id === $service->getKey(), 404);
    }
}
