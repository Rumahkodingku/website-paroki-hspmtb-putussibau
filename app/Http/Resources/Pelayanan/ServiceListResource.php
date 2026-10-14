<?php

namespace App\Http\Resources\Pelayanan;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A service as the admin Daftar Pelayanan table needs it.
 *
 * `registration_method` is the "Pendaftaran" column. It reports what is
 * configured on this service only — an emergency service is "Kontak Langsung",
 * a service with a link is "Google Form", a service with its own number is
 * "Kontak Langsung", and anything else is "Tidak Diatur". The global secretariat
 * fallback is a public-page concern and is not folded in here, so the column
 * tells an admin what they set, not what a visitor would get.
 *
 * @mixin Service
 */
class ServiceListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'thumbnail' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('thumb')),
            'thumbnail_alt' => $this->thumbnail_alt,
            'category' => $this->whenLoaded('serviceCategory', fn (): array => [
                'id' => $this->serviceCategory->getKey(),
                'name' => $this->serviceCategory->name,
            ]),
            'registration_method' => $this->registrationMethod(),
            'is_active' => $this->is_active,
            'is_emergency' => $this->is_emergency,
            'sort_order' => $this->sort_order,
            'updated_at' => $this->updated_at->toIso8601String(),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }

    private function registrationMethod(): string
    {
        if ($this->is_emergency) {
            return 'direct_contact';
        }

        if ($this->hasFormUrl()) {
            return 'google_form';
        }

        if ($this->hasOwnContact()) {
            return 'direct_contact';
        }

        return 'unset';
    }
}
