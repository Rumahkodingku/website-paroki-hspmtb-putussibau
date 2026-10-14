<?php

namespace App\Http\Controllers\Pelayanan;

use App\Actions\Pelayanan\ReorderServiceItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelayanan\ReorderServiceItemsRequest;
use App\Http\Requests\Pelayanan\StoreServiceFaqRequest;
use App\Http\Requests\Pelayanan\UpdateServiceFaqRequest;
use App\Models\Service;
use App\Models\ServiceFaq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ServiceFaqController extends Controller
{
    public function store(StoreServiceFaqRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $data['service_id'] = $service->getKey();
        $data['sort_order'] = $data['sort_order']
            ?? ((int) $service->faqs()->max('sort_order')) + 1;

        ServiceFaq::create($data);
        $service->touch();

        return to_route('services.edit', ['service' => $service, 'tab' => 'faq'])
            ->with('success', __('pelayanan.flash.faq_created'));
    }

    public function update(
        UpdateServiceFaqRequest $request,
        Service $service,
        ServiceFaq $faq,
    ): RedirectResponse {
        $this->ensureBelongsTo($service, $faq);

        $faq->update($request->validated());
        $service->touch();

        return back()->with('success', __('pelayanan.flash.faq_updated'));
    }

    public function destroy(Service $service, ServiceFaq $faq): RedirectResponse
    {
        Gate::authorize('update', $service);
        $this->ensureBelongsTo($service, $faq);

        $faq->delete();
        $service->touch();

        return back()->with('success', __('pelayanan.flash.faq_deleted'));
    }

    public function reorder(
        ReorderServiceItemsRequest $request,
        Service $service,
        ReorderServiceItemsAction $action,
    ): RedirectResponse {
        $ids = $request->validated('ids');

        $action->handle($service, ServiceFaq::class, $ids);

        return back()->with('success', __('pelayanan.flash.faq_reordered'));
    }

    private function ensureBelongsTo(Service $service, ServiceFaq $faq): void
    {
        abort_unless($faq->service_id === $service->getKey(), 404);
    }
}
