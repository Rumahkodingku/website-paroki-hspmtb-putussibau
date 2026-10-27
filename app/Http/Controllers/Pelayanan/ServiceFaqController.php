<?php

namespace App\Http\Controllers\Pelayanan;

use App\Actions\Pelayanan\ReorderServiceItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelayanan\ReorderServiceItemsRequest;
use App\Http\Requests\Pelayanan\StoreServiceFaqRequest;
use App\Http\Requests\Pelayanan\UpdateServiceFaqRequest;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ServiceFaqController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(StoreServiceFaqRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $data['service_id'] = $service->getKey();
        $data['sort_order'] = $data['sort_order']
            ?? ((int) $service->faqs()->max('sort_order')) + 1;

        DB::transaction(function () use ($data, $service): void {
            $item = ServiceFaq::query()->create($data);
            $service->touch();
            $this->audit->created('service_faqs', $item);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.faq_created')]);

        return to_route('services.edit', ['service' => $service, 'tab' => 'faq']);
    }

    public function update(
        UpdateServiceFaqRequest $request,
        Service $service,
        ServiceFaq $faq,
    ): RedirectResponse {
        $this->ensureBelongsTo($service, $faq);

        DB::transaction(function () use ($request, $faq, $service): void {
            $before = $this->audit->snapshot($faq, 'service_faqs');
            $faq->update($request->validated());
            $service->touch();
            $this->audit->updated('service_faqs', $faq, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.faq_updated')]);

        return back();
    }

    public function destroy(Service $service, ServiceFaq $faq): RedirectResponse
    {
        Gate::authorize('update', $service);
        $this->ensureBelongsTo($service, $faq);

        DB::transaction(function () use ($faq, $service): void {
            $before = $this->audit->snapshot($faq, 'service_faqs');
            $faq->delete();
            $service->touch();
            $this->audit->deleted('service_faqs', $faq, $before);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.faq_deleted')]);

        return back();
    }

    public function reorder(
        ReorderServiceItemsRequest $request,
        Service $service,
        ReorderServiceItemsAction $action,
    ): RedirectResponse {
        $ids = $request->validated('ids');

        $action->handle($service, ServiceFaq::class, $ids);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('pelayanan.flash.faq_reordered')]);

        return back();
    }

    private function ensureBelongsTo(Service $service, ServiceFaq $faq): void
    {
        abort_unless($faq->service_id === $service->getKey(), 404);
    }
}
