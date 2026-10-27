<?php

namespace App\Actions\Audit;

use App\Models\AuditEvent;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The single writer for audit_events.
 *
 * Everything that reaches this class has already been built by AuditLogger,
 * which owns actor context and model snapshots. This action is the last gate:
 * it refuses unknown codes, applies the allowlists, redacts, summarizes and
 * truncates, and then inserts exactly one row.
 */
class RecordAuditEventAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): AuditEvent
    {
        $module = $this->string($payload['module'] ?? '', 'module');
        $action = $this->string($payload['action'] ?? '', 'action');
        $result = $this->string($payload['result'] ?? 'success', 'result');

        $this->assertCode('modules', $module);
        $this->assertCode('actions', $action);
        $this->assertCode('results', $result);

        $actorType = $this->string($payload['actor_type'] ?? 'anonymous', 'actor_type');

        if (! in_array($actorType, ['user', 'system', 'anonymous'], true)) {
            throw new InvalidArgumentException("Unknown audit actor type [{$actorType}].");
        }

        $snapshot = $this->filterSnapshot($module, $payload['before'] ?? null);
        $after = $this->filterSnapshot($module, $payload['after'] ?? null);

        $metadata = $this->filterMetadata($module, $payload['metadata'] ?? []);

        $summarized = array_values(array_unique([
            ...$snapshot['summarized'],
            ...$after['summarized'],
        ]));

        if ($summarized !== []) {
            $metadata['summarized_fields'] = $summarized;
        }

        $truncated = array_values(array_unique([
            ...$snapshot['truncated'],
            ...$after['truncated'],
        ]));

        if ($truncated !== []) {
            $metadata['truncated_fields'] = $truncated;
        }

        if ($snapshot['capped'] || $after['capped']) {
            $metadata['snapshot_truncated'] = true;
        }

        $attributes = [
            'actor_id' => $payload['actor_id'] ?? null,
            'actor_type' => $actorType,
            'actor_name' => $this->nullableLimit($payload['actor_name'] ?? null, 150),
            'actor_email' => $this->nullableLimit($payload['actor_email'] ?? null, 255),
            'action' => $action,
            'module' => $module,
            'subject_type' => $this->nullableLimit($payload['subject_type'] ?? null, 150),
            'subject_id' => $this->nullableLimit($payload['subject_id'] ?? null, 64),
            'subject_label' => $this->nullableLimit($payload['subject_label'] ?? null, 255),
            'description' => Str::limit($this->string($payload['description'] ?? '', 'description'), 500),
            'result' => $result,
            'before' => $snapshot['values'],
            'after' => $after['values'],
            'metadata' => $metadata === [] ? null : $metadata,
            'request_id' => $this->nullableLimit($payload['request_id'] ?? null, 64),
            'ip_address' => $this->nullableLimit($payload['ip_address'] ?? null, 45),
            'user_agent' => $this->nullableLimit($payload['user_agent'] ?? null, 500),
            'occurred_at' => $payload['occurred_at'] ?? Carbon::now(),
            'idempotency_key' => $this->nullableLimit($payload['idempotency_key'] ?? null, 120),
        ];

        if ($attributes['idempotency_key'] !== null) {
            return $this->firstOrCreate($attributes);
        }

        return AuditEvent::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function firstOrCreate(array $attributes): AuditEvent
    {
        $key = $attributes['idempotency_key'];

        try {
            return AuditEvent::query()->firstOrCreate(
                ['idempotency_key' => $key],
                $attributes,
            );
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            $existing = AuditEvent::query()
                ->where('idempotency_key', $key)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    /**
     * @return array{
     *     values: array<string, mixed>|null,
     *     summarized: list<string>,
     *     truncated: list<string>,
     *     capped: bool
     * }
     */
    private function filterSnapshot(string $module, mixed $snapshot): array
    {
        if (! is_array($snapshot) || $snapshot === []) {
            return ['values' => null, 'summarized' => [], 'truncated' => [], 'capped' => false];
        }

        $allowed = $this->allowedFields($module);
        $summarizedFields = array_values((array) config("audit.summarize.{$module}", []));
        $maxLength = (int) config('audit.snapshot.max_value_length', 1000);

        $values = [];
        $summarized = [];
        $truncated = [];

        foreach ($snapshot as $key => $value) {
            if (! is_string($key) || ! in_array($key, $allowed, true)) {
                continue;
            }

            if ($this->isRedacted($key)) {
                continue;
            }

            $normalized = $this->normalize($value);

            if (in_array($key, $summarizedFields, true) && is_string($normalized)) {
                $values[$key] = '[ringkas: '.number_format(strlen($normalized), 0, ',', '.').' karakter]';
                $summarized[] = $key;

                continue;
            }

            if (is_string($normalized) && mb_strlen($normalized) > $maxLength) {
                $values[$key] = Str::limit($normalized, $maxLength, '…');
                $truncated[] = $key;

                continue;
            }

            $values[$key] = $normalized;
        }

        $capped = false;
        $maxFields = (int) config('audit.snapshot.max_fields', 30);

        if (count($values) > $maxFields) {
            $values = array_slice($values, 0, $maxFields, true);
            $capped = true;
        }

        return [
            'values' => $values === [] ? null : $values,
            'summarized' => $summarized,
            'truncated' => $truncated,
            'capped' => $capped,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filterMetadata(string $module, mixed $metadata): array
    {
        if (! is_array($metadata) || $metadata === []) {
            return [];
        }

        $allowed = array_values((array) config("audit.metadata.{$module}", []));
        $result = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || ! in_array($key, $allowed, true)) {
                continue;
            }

            if ($this->isRedacted($key)) {
                continue;
            }

            $result[$key] = $this->normalize($value);
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function allowedFields(string $module): array
    {
        if ($module === 'settings') {
            $groups = (array) config('site-settings.groups', []);

            if ($groups === []) {
                return [];
            }

            /** @var array<string, list<string>> $groups */
            return array_values(array_unique(array_merge(...array_values($groups))));
        }

        /** @var list<string> $fields */
        $fields = array_values((array) config("audit.fields.{$module}", []));

        return $fields;
    }

    private function isRedacted(string $key): bool
    {
        $normalized = Str::lower($key);

        foreach ((array) config('audit.redact_keys', []) as $redacted) {
            $needle = Str::lower((string) $redacted);

            if ($normalized === $needle || str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toIso8601String();
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                if (is_string($key) && $this->isRedacted($key)) {
                    continue;
                }

                $result[$key] = $this->normalize($item);
            }

            return $result;
        }

        if (is_object($value)) {
            return null;
        }

        return $value;
    }

    private function assertCode(string $list, string $code): void
    {
        if (! in_array($code, (array) config("audit.{$list}", []), true)) {
            throw new InvalidArgumentException("Unknown audit {$list} code [{$code}].");
        }
    }

    private function string(mixed $value, string $field): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Audit payload [{$field}] must be a non-empty string.");
        }

        return $value;
    }

    private function nullableLimit(mixed $value, int $limit): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit($value, $limit);
    }
}
