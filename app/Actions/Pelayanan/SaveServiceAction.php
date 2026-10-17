<?php

namespace App\Actions\Pelayanan;

use App\Models\Service;
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
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Service $service, array $attributes): Service
    {
        $documentIds = $attributes['document_ids'] ?? null;
        unset($attributes['document_ids']);

        return DB::transaction(function () use ($service, $attributes, $documentIds): Service {
            $service->fill($attributes);
            $service->save();

            if (is_array($documentIds)) {
                $service->documents()->sync($documentIds);
            }

            return $service;
        });
    }
}
