<?php

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('auditlogs.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'actor' => ['nullable', 'string', 'max:50'],
            'module' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'string', 'max:50'],
            'result' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', 'string', 'max:50'],
            'direction' => ['nullable', 'string', 'max:10'],
            'per_page' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $from = $this->input('from');
                $to = $this->input('to');

                if (! is_string($from) || ! is_string($to)) {
                    return;
                }

                if (! Carbon::canBeCreatedFromFormat($from, 'Y-m-d')
                    || ! Carbon::canBeCreatedFromFormat($to, 'Y-m-d')) {
                    return;
                }

                $timezone = (string) config('app.display_timezone');
                $fromDate = Carbon::createFromFormat('Y-m-d', $from, $timezone);
                $toDate = Carbon::createFromFormat('Y-m-d', $to, $timezone);

                if ($fromDate->greaterThan($toDate)) {
                    $validator->errors()->add('to', 'Tanggal akhir harus setelah tanggal awal.');

                    return;
                }

                if ($fromDate->diffInDays($toDate) > (int) config('audit.max_date_range_days', 366)) {
                    $validator->errors()->add('to', 'Rentang tanggal paling lama satu tahun.');
                }
            },
        ];
    }

    /**
     * @return array{search: string, from: string, to: string, actor: string, module: string, action: string, result: string, sort: string, direction: string}
     */
    public function filters(): array
    {
        return [
            'search' => trim($this->string('search')->toString()),
            'from' => $this->string('from')->toString(),
            'to' => $this->string('to')->toString(),
            'actor' => $this->string('actor')->toString(),
            'module' => $this->string('module')->toString(),
            'action' => $this->string('action')->toString(),
            'result' => $this->string('result')->toString(),
            'sort' => trim($this->string('sort')->toString()),
            'direction' => trim($this->string('direction')->toString()),
        ];
    }

    public function fromUtc(): ?Carbon
    {
        return $this->dateBoundary('from', 'startOfDay');
    }

    public function toUtc(): ?Carbon
    {
        return $this->dateBoundary('to', 'endOfDay');
    }

    public function perPage(): int
    {
        $value = (int) $this->input('per_page', config('audit.pagination.default', 10));

        /** @var list<int> $allowed */
        $allowed = array_map(intval(...), (array) config('audit.pagination.allowed', [10]));

        return in_array($value, $allowed, true) ? $value : (int) config('audit.pagination.default', 10);
    }

    private function dateBoundary(string $key, string $method): ?Carbon
    {
        $value = $this->string($key)->toString();

        if ($value === '') {
            return null;
        }

        $date = Carbon::createFromFormat(
            'Y-m-d',
            $value,
            (string) config('app.display_timezone'),
        );

        return $date->{$method}()->utc();
    }
}
