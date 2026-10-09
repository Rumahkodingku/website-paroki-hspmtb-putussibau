<?php

namespace App\Http\Requests\MassSchedule;

use App\Concerns\ValidatesActiveLocationReference;
use App\Models\ScheduleNotice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates creating a schedule-change notice.
 *
 * The one rule here that needs explaining is the target exclusivity. A notice
 * has three optional references — a location, a recurring schedule, a special
 * mass — and roadmap 4.4 requires the filled ones to be consistent. The choice
 * made is stricter than "consistent": at most one target, and none for a notice
 * that applies to everyone.
 *
 * A notice pointing at both a location and a schedule inside it has no reading:
 * does it apply at that location only, or to that schedule wherever it appears?
 * The public banner would have to pick one, and the other reading would be
 * silently lost. Forbidding the combination makes the answer unambiguous, and an
 * admin who wanted two notices writes two.
 *
 * @see docs/DECISIONS.md D-34
 */
class StoreScheduleNoticeRequest extends FormRequest
{
    use ValidatesActiveLocationReference;

    /**
     * The three mutually exclusive target fields.
     *
     * @var list<string>
     */
    public const TARGETS = ['location_id', 'mass_schedule_id', 'special_mass_id'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', ScheduleNotice::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'starts_at' => $this->toUtc($this->input('starts_at')),
            'ends_at' => $this->toUtc($this->input('ends_at')),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'mass_schedule_id' => ['nullable', 'integer', Rule::exists('mass_schedules', 'id')],
            'special_mass_id' => ['nullable', 'integer', Rule::exists('special_masses', 'id')],
        ];

        return $rules;
    }

    /**
     * At most one target.
     *
     * Written as a check rather than as a rule because Laravel 13 has no
     * `prohibited_with` — the family is prohibited, prohibited_if,
     * prohibited_unless, and nothing that takes a list of other fields. Reaching
     * for one that does not exist produces a BadMethodCallException at
     * validation time, which surfaces as a 500 rather than as a field error.
     *
     * `prohibited_unless` could express it as a chain of three, at the cost of
     * a message per field that reads "unless another field is empty" — accurate
     * and useless to an admin holding one picker. This reports on the *second*
     * target filled, which is the one they will change.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var array<string, mixed> $input */
            $input = $this->all();

            $filled = array_values(array_filter(
                self::TARGETS,
                static fn (string $target): bool => ($input[$target] ?? null) !== null
                    && ($input[$target] ?? '') !== ''
            ));

            if (count($filled) > 1) {
                foreach (array_slice($filled, 1) as $target) {
                    $validator->errors()->add(
                        $target,
                        __('Isi paling banyak satu target. Kosongkan semua untuk notice umum.')
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after_or_equal' => 'Akhir tampil tidak boleh lebih awal dari mulai tampil.',
            'starts_at.required' => 'Waktu mulai tampil wajib diisi.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function scheduleNoticePayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        if (($validated['ends_at'] ?? null) === '') {
            $validated['ends_at'] = null;
        }

        return $validated;
    }

    /**
     * A posted local wall clock becomes UTC; an empty optional field is null.
     */
    protected function toUtc(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value === '' ? null : $value;
        }

        return Carbon::parse($value, config('app.display_timezone'))->utc();
    }
}
