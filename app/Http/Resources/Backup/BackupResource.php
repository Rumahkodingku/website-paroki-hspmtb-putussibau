<?php

namespace App\Http\Resources\Backup;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Backup
 */
class BackupResource extends JsonResource
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
            'original_filename' => $this->original_filename,
            'file_size' => $this->file_size,
            'checksum' => $this->checksum,
            'format_version' => $this->format_version,
            'note' => $this->note,
            'failure_code' => $this->failure_code,
            'failure_message' => $this->failure_message,
            'created_by_label' => $this->created_by_label,
            'scheduled_slot' => $this->scheduled_slot,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'file_missing_at' => $this->file_missing_at?->toIso8601String(),
            'protected_until' => $this->protected_until?->toIso8601String(),
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at->toIso8601String(),
            'is_available' => $this->isAvailable(),
            'restores' => $this->whenLoaded(
                'restores',
                fn (): array => BackupRestoreResource::collection($this->restores)->resolve(),
            ),
            'can' => [
                'download' => $request->user()?->can('download', $this->resource) ?? false,
                'restore' => $request->user()?->can('restore', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
