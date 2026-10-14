<?php

namespace App\Http\Requests\Pelayanan;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reordering a service's requirements, steps or FAQs.
 *
 * Only the shape is validated here — a list of integer ids. That an id belongs
 * to *this* service is checked by ReorderServiceItemsAction, which is the only
 * place that knows which child table the request came through, and which fails
 * the whole transaction rather than reordering a subset.
 *
 * @see docs/DECISIONS.md D-54
 */
class ReorderServiceItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->service()) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Daftar urutan tidak boleh kosong.',
            'ids.array' => 'Daftar urutan tidak valid.',
            'ids.*.distinct' => 'Daftar urutan memuat item yang sama lebih dari sekali.',
        ];
    }

    private function service(): Service
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $service;
    }
}
