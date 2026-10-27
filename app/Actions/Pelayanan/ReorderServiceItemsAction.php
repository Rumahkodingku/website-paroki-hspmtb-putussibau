<?php

namespace App\Actions\Pelayanan;

use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceRequirement;
use App\Models\ServiceStep;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Renumbers a service's requirements, steps or FAQs from a submitted order.
 *
 * This is an Action rather than controller code because it is the one operation
 * in the module that writes more than one row and must be all-or-nothing: a
 * reorder that updated half its rows and then hit an id from another service
 * would leave two items claiming the same position. The transaction and the
 * membership check are why it exists.
 *
 * Each row is saved through the model, not with a query-builder update, and the
 * service is touched once at the end, so its "Terakhir diperbarui" moves even
 * though a reorder changes only sort_order (SVC-03).
 *
 * @see docs/DECISIONS.md D-54
 */
final class ReorderServiceItemsAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int|string>  $ids  the new order, first item first
     */
    public function handle(Service $service, string $modelClass, array $ids): void
    {
        $ordered = array_map('intval', $ids);

        DB::transaction(function () use ($service, $modelClass, $ordered): void {
            $items = $modelClass::query()
                ->where('service_id', $service->getKey())
                ->whereIn('id', $ordered)
                ->get()
                ->keyBy('id');

            if ($items->count() !== count($ordered)) {
                throw ValidationException::withMessages([
                    'ids' => 'Daftar urutan memuat item yang bukan milik layanan ini.',
                ]);
            }

            foreach ($ordered as $position => $id) {
                $items->get($id)?->update(['sort_order' => $position]);
            }

            // SVC-03: a reorder changes only sort_order, so the parent's
            // updated_at would not move without this.
            $service->touch();

            $module = match ($modelClass) {
                ServiceRequirement::class => 'service_requirements',
                ServiceStep::class => 'service_steps',
                ServiceFaq::class => 'service_faqs',
                default => 'services',
            };

            $this->audit->recordModel(
                $module,
                'reordered',
                'success',
                $service,
                [],
                [],
                [
                    'count' => count($ordered),
                    'ids' => array_slice($ordered, 0, 100),
                ],
            );
        });
    }
}
