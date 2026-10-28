<?php

namespace App\Http\Resources\Backup;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Backup
 */
class BackupListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'source' => $this->source->value,
            'source_label' => $this->source->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'validation_status' => $this->validation_status->value,
            'validation_label' => $this->validation_status->label(),
            'file_size' => $this->file_size,
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at->toIso8601String(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'created_by_label' => $this->created_by_label,
            'scheduled_slot' => $this->scheduled_slot,
            'is_available' => $this->isAvailable(),
            'file_missing_at' => $this->file_missing_at?->toIso8601String(),
            'can' => [
                'download' => $request->user()?->can('download', $this->resource) ?? false,
                'restore' => $request->user()?->can('restore', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
