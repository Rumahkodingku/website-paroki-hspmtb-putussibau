<?php

namespace App\Http\Requests\MassSchedule;

use App\Concerns\ValidatesActiveLocationReference;
use App\Models\MassSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates copying a recurring mass to another location.
 *
 * The request carries only the destination. Day, time, name, language and notes
 * come from the source record, so asking for them again would mean the form could
 * contradict the schedule it claims to be copying.
 *
 * Two things are still checked, and both are about the destination rather than
 * the copy. It must be an active location, for the same reason a new schedule
 * must be (MASS-03): copying onto a deactivated location creates a record that is
 * invisible from the moment it is written. And it must not already hold that day
 * and time, because a duplicate is not a useful second copy (roadmap 5.7).
 */
class DuplicateMassScheduleRequest extends FormRequest
{
    use ValidatesActiveLocationReference;

    /**
     * Determine if the user is authorized to make this request.
     *
     * This creates a new mass schedule, so it authorizes create rather than
     * update — even though the route hangs off an existing record.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', MassSchedule::class);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'location_id' => [
                ...$this->activeLocationRule(),
                // MASS-06 against the *source's* day and time, because that is
                // what the copy will carry.
                Rule::unique('mass_schedules')
                    ->where('location_id', $this->input('location_id'))
                    ->where('day_of_week', $this->source()->day_of_week->value)
                    ->where('start_time', $this->source()->start_time),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location_id.exists' => 'Lokasi tujuan tidak tersedia atau sedang nonaktif.',
            'location_id.unique' => 'Lokasi tujuan sudah memiliki jadwal pada hari dan jam yang sama.',
        ];
    }

    /**
     * @return array{location_id: int}
     */
    public function destinationPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return ['location_id' => (int) $validated['location_id']];
    }

    /**
     * The schedule being copied, typed for PHPStan.
     */
    private function source(): MassSchedule
    {
        /** @var MassSchedule $massSchedule */
        $massSchedule = $this->route('mass_schedule');

        return $massSchedule;
    }
}
