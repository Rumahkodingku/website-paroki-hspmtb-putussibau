<?php

namespace App\Http\Resources\Backup;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
class BackupSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'settings' => $this->resource['settings'] ?? [],
            'timezones' => $this->resource['timezones'] ?? [],
            'readiness' => $this->resource['readiness'] ?? [],
        ];
    }
}
