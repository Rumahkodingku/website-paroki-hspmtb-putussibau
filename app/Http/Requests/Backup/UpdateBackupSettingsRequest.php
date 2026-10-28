<?php

namespace App\Http\Requests\Backup;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings', Backup::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'schedule_enabled' => ['required', 'boolean'],
            'database_enabled' => ['required', 'boolean'],
            'full_enabled' => ['required', 'boolean'],
            'database_time' => ['required', 'date_format:H:i'],
            'full_day' => ['required', 'integer', 'between:0,6'],
            'full_time' => ['required', 'date_format:H:i'],
            'timezone' => [
                'required',
                'timezone',
                Rule::in((array) config('backups.timezones')),
            ],
            'retention_daily_days' => ['required', 'integer', 'between:1,365'],
            'retention_weekly_weeks' => ['required', 'integer', 'between:1,52'],
            'retention_invalid_upload_days' => ['required', 'integer', 'between:1,90'],
            'retention_safety_days' => ['required', 'integer', 'between:1,365'],
            'max_upload_megabytes' => ['required', 'integer', 'between:1,2048'],
            'max_expanded_megabytes' => ['required', 'integer', 'between:1,8192'],
            'max_files' => ['required', 'integer', 'between:1,100000'],
            'max_compression_ratio' => ['required', 'integer', 'between:1,1000'],
            'low_disk_threshold_megabytes' => ['required', 'integer', 'between:0,102400'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ((bool) $this->input('schedule_enabled')
                    && ! (bool) $this->input('database_enabled')
                    && ! (bool) $this->input('full_enabled')) {
                    $validator->errors()->add(
                        'database_enabled',
                        'Pilih minimal satu jenis backup saat jadwal diaktifkan.',
                    );
                }

                if ((int) $this->input('max_expanded_megabytes') < (int) $this->input('max_upload_megabytes')) {
                    $validator->errors()->add(
                        'max_expanded_megabytes',
                        'Batas hasil ekstraksi harus lebih besar atau sama dengan batas unggahan.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $validated;
    }
}
