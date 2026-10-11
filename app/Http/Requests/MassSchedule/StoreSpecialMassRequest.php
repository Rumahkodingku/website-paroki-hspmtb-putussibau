<?php

namespace App\Http\Requests\MassSchedule;

use App\Concerns\ValidatesActiveLocationReference;
use App\Models\SpecialMass;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Validates creating a one-off mass.
 *
 * The datetime fields arrive as whatever an <input type="datetime-local"> sends
 * — a local wall clock with no offset. That string is interpreted in
 * app.display_timezone and converted to UTC here, in one place, because D-01
 * says storage is UTC and a value posted as "2026-12-25 09:00" otherwise means
 * two different instants depending on which machine reads it.
 *
 * end_at may be absent, which means the mass has no stated finish time.
 */
class StoreSpecialMassRequest extends FormRequest
{
    use ValidatesActiveLocationReference;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', SpecialMass::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_at' => $this->toUtc($this->input('start_at')),
            'end_at' => $this->toUtc($this->input('end_at')),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'location_id' => $this->activeLocationRule(),
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_at' => ['required', 'date'],
            // after_or_equal rather than after: a mass that starts and ends at
            // the same minute is odd but not invalid, and rejecting it would
            // force an admin to invent a duration.
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location_id.exists' => 'Lokasi yang dipilih tidak tersedia atau sedang nonaktif.',
            'start_at.required' => 'Waktu mulai wajib diisi.',
            'end_at.after_or_equal' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function specialMassPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        if (($validated['end_at'] ?? null) === '') {
            $validated['end_at'] = null;
        }

        return $validated;
    }

    /**
     * Read a posted local wall clock as Asia/Pontianak and return UTC.
     *
     * An empty string is a cleared optional field, not the epoch.
     */
    protected function toUtc(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value === '' ? null : $value;
        }

        return Carbon::parse($value, config('app.display_timezone'))->utc();
    }
}
