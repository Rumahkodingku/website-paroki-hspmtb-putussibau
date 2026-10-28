<?php

namespace App\Http\Resources\Backup;

use App\Models\BackupRestore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BackupRestore
 */
class BackupRestoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'database_result' => $this->database_result?->value,
            'database_result_label' => $this->database_result?->label(),
            'files_result' => $this->files_result?->value,
            'files_result_label' => $this->files_result?->label(),
            'safety_backup_id' => $this->safety_backup_id,
            'safety_backup_status' => $this->whenLoaded(
                'safetyBackup',
                fn (): ?string => $this->safetyBackup?->status->label(),
            ),
            'requested_by_label' => $this->requested_by_label,
            'created_at' => $this->created_at->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'duration_ms' => $this->duration_ms,
            'failure_code' => $this->failure_code,
            'failure_message' => $this->failure_message,
            'verification' => $this->verification,
            'can_confirm' => $this->isReadyForConfirmation(),
        ];
    }
}
