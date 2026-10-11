<?php

namespace App\Http\Requests\MassSchedule;

use App\Models\ScheduleNotice;

/**
 * Validates changing a schedule-change notice.
 *
 * Inherits everything from the store request — the same field rules, the same
 * timezone conversion, the same at-most-one-target rule — and differs only in
 * which ability authorizes it. Duplicating those rules would let the two drift
 * into accepting different payloads, which shows up later as a notice that
 * cannot be saved.
 */
class UpdateScheduleNoticeRequest extends StoreScheduleNoticeRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('schedule_notice'));
    }

    /**
     * The bound record, typed for PHPStan.
     */
    public function scheduleNotice(): ScheduleNotice
    {
        /** @var ScheduleNotice $notice */
        $notice = $this->route('schedule_notice');

        return $notice;
    }
}
