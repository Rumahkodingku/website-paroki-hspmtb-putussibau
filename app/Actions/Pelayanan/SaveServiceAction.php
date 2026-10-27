<?php

namespace App\Actions\Pelayanan;

use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Writes a service and its related documents together.
 *
 * The service's own columns and the document pivot are one admin save, so the
 * write belongs in a transaction. document_ids is removed before the fill:
 * it is a pivot, not a column.
 */
class SaveServiceAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Service $service, array $attributes): Service
    {
        $documentIds = $attributes['document_ids'] ?? null;
        unset($attributes['document_ids']);

        return DB::transaction(function () use ($service, $attributes, $documentIds): Service {
            $isNew = ! $service->exists;
            $before = $isNew ? [] : $this->audit->snapshot($service, 'services');

            $service->fill($attributes);
            $service->save();

            if (is_array($documentIds)) {
                $service->documents()->sync($documentIds);
            }

            if ($isNew) {
                $this->audit->created('services', $service);
            } else {
                $this->audit->updated('services', $service, $before);
            }

            return $service;
        });
    }
}
