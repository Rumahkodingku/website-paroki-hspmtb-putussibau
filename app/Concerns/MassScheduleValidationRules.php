<?php

namespace App\Concerns;

use App\Enums\DayOfWeek;
use App\Models\MassSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules for a recurring mass, shared by the store and the update request.
 *
 * The duplicate check is the application half of MASS-06; the unique index on
 * mass_schedules is the other half, and roadmap 5.2 asks for both — the rule so
 * an admin gets a sentence they can act on, the index so two simultaneous
 * requests cannot both win.
 *
 * It is written as an explicit method the request calls rather than a
 * `withValidator` hook, because the location rules need the same hook and two
 * traits defining it would be a fatal collision.
 *
 * @see docs/DECISIONS.md D-36
 * @see docs/PRD.md D-09, 7.3 MASS-06
 */
trait MassScheduleValidationRules
{
    use ValidatesActiveLocationReference;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function massScheduleRules(): array
    {
        return [
            'location_id' => $this->activeLocationRule(),
            'day_of_week' => ['required', 'integer', Rule::enum(DayOfWeek::class)],
            // A wall clock, and the rule says so. date_format:H:i rejects the
            // seconds MySQL actually stores, so what reaches the model is always
            // the shape <input type="time"> produces.
            'start_time' => ['required', 'string', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * MASS-06 as a rule, expressed against the submitted location, day and time.
     *
     * @param  MassSchedule|null  $ignore  the record being updated
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function uniqueOccurrenceRule(?MassSchedule $ignore = null): array
    {
        /** @var array<string, mixed> $input */
        $input = $this->all();

        return [
            'required',
            // withoutTrashed() because a soft-deleted row still sits in the
            // table and would otherwise hold the slot (D-40). The index was
            // rebuilt with a generated column so that a deleted schedule frees
            // its slot; without this the rule would say "already taken" for a
            // slot the database is now perfectly willing to accept.
            Rule::unique('mass_schedules')
                ->where('location_id', $input['location_id'] ?? null)
                ->where('day_of_week', $input['day_of_week'] ?? null)
                ->where('start_time', $this->timeForDatabase($input['start_time'] ?? null))
                ->ignore($ignore?->getKey())
                ->withoutTrashed(),
        ];
    }

    /**
     * The duplicate check, repeated as an explicit lookup.
     *
     * Rule::unique runs a count query of its own and is skipped entirely once
     * another rule has failed, so a payload that is wrong in two places reports
     * only one error. This runs after the field rules have passed, which means
     * it is the one that decides when a duplicate is actually reported.
     *
     * It is also the layer that survives the unique index: two requests can both
     * pass every rule and every lookup here, and only one insert can win. The
     * index rejects the loser, and MassScheduleController::duplicate turns that
     * into a message rather than a 500.
     */
    protected function rejectDuplicateOccurrence(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var array<string, mixed> $input */
            $input = $this->all();

            if (! isset($input['location_id'], $input['day_of_week'], $input['start_time'])) {
                return;
            }

            $massSchedule = $this->route('mass_schedule');

            $existing = MassSchedule::query()
                ->where('location_id', $input['location_id'])
                ->where('day_of_week', $input['day_of_week'])
                ->where('start_time', $this->timeForDatabase($input['start_time']));

            // The update case has to exclude the row being edited, or re-saving
            // a schedule without touching its day, time or location is reported
            // as a duplicate of itself.
            if ($massSchedule instanceof MassSchedule) {
                $existing->whereKeyNot($massSchedule->getKey());
            }

            if ($existing->exists()) {
                $validator->errors()->add(
                    'location_id',
                    __('Sudah ada jadwal pada lokasi, hari, dan jam tersebut.')
                );
            }
        });
    }

    /**
     * "07:30" as MySQL stores it.
     *
     * The unique index compares against the column, which holds seconds. An
     * absent or unusable time yields null, and the lookup above then matches
     * nothing — the field rules already reported the real problem.
     */
    protected function timeForDatabase(mixed $time): ?string
    {
        return is_string($time) && preg_match('/^\d{2}:\d{2}$/', $time) === 1
            ? $time.':00'
            : null;
    }

    /**
     * "7:30" to "07:30", so a hand-written request is not rejected for the sake
     * of a leading zero.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function normaliseTimeInput(array $input): array
    {
        $time = $input['start_time'] ?? null;

        if (is_string($time) && preg_match('/^(\d{1,2}):(\d{2})$/', trim($time), $matches) === 1) {
            $input['start_time'] = sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        }

        return $input;
    }

    /**
     * @return array<string, string>
     */
    protected function massScheduleMessages(): array
    {
        return [
            'location_id.exists' => 'Lokasi yang dipilih tidak tersedia atau sedang nonaktif.',
            'start_time.date_format' => 'Format jam harus HH:MM, misalnya 07:30.',
            'day_of_week.enum' => 'Hari yang dipilih tidak valid.',
            'location_id.unique' => 'Sudah ada jadwal pada lokasi, hari, dan jam tersebut.',
        ];
    }
}
