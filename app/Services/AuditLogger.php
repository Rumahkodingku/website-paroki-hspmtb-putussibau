<?php

namespace App\Services;

use App\Actions\Audit\RecordAuditEventAction;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * The application's audit API.
 *
 * Callers pass a model and a module code; this class decides what a safe
 * snapshot looks like, which fields actually changed, and who the actor was.
 * It never accepts a raw request payload or caller-supplied metadata without
 * the allowlist in RecordAuditEventAction doing the final filtering.
 */
class AuditLogger
{
    public function __construct(private readonly RecordAuditEventAction $recorder) {}

    /**
     * The allowlisted attributes of a model, normalised for JSON.
     *
     * @return array<string, mixed>
     */
    public function snapshot(Model $subject, string $module): array
    {
        $attributes = $subject->getAttributes();
        $result = [];

        foreach ($this->allowedFields($module) as $field) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }

            $result[$field] = $this->normalize($subject->getAttribute($field));
        }

        return $result;
    }

    /**
     * Record a model event whose action is narrower than created/updated.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $metadata
     */
    public function recordModel(
        string $module,
        string $action,
        string $result,
        Model $subject,
        array $before = [],
        array $after = [],
        array $metadata = [],
        ?string $label = null,
    ): void {
        $label = $label ?? $this->label($subject);

        $this->record([
            'module' => $module,
            'action' => $action,
            'result' => $result,
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'subject_label' => $label,
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
            'description' => $this->describe($module, $action, $label),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function created(
        string $module,
        Model $subject,
        ?string $label = null,
        array $metadata = [],
    ): void {
        $label = $label ?? $this->label($subject);

        $this->record([
            'module' => $module,
            'action' => 'created',
            'result' => 'success',
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'subject_label' => $label,
            'after' => $this->snapshot($subject, $module),
            'metadata' => $metadata,
            'description' => $this->describe($module, 'created', $label),
        ]);
    }

    /**
     * Record an update from a snapshot taken before the mutation.
     *
     * Only fields whose value actually changed reach the event. Passing an
     * action makes a status transition read as "published" instead of a generic
     * "updated", without writing two rows for one save.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $metadata
     */
    public function updated(
        string $module,
        Model $subject,
        array $before,
        ?string $action = null,
        ?string $label = null,
        array $metadata = [],
    ): void {
        $after = $this->snapshot($subject, $module);
        $changedBefore = [];
        $changedAfter = [];

        $keys = array_values(array_unique([
            ...array_keys($before),
            ...array_keys($after),
        ]));

        foreach ($keys as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ($old === $new) {
                continue;
            }

            $changedBefore[$key] = $old;
            $changedAfter[$key] = $new;
        }

        if ($changedBefore === [] && $changedAfter === []) {
            return;
        }

        $action = $action ?? 'updated';
        $label = $label ?? $this->label($subject);

        $this->record([
            'module' => $module,
            'action' => $action,
            'result' => 'success',
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'subject_label' => $label,
            'before' => $changedBefore,
            'after' => $changedAfter,
            'metadata' => $metadata,
            'description' => $this->describe($module, $action, $label),
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function deleted(string $module, Model $subject, array $before, ?string $label = null): void
    {
        $label = $label ?? $this->label($subject);

        $this->record([
            'module' => $module,
            'action' => 'deleted',
            'result' => 'success',
            'subject_type' => $subject::class,
            'subject_id' => (string) $subject->getKey(),
            'subject_label' => $label,
            'before' => $before,
            'after' => null,
            'description' => $this->describe($module, 'deleted', $label),
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedKeys
     */
    public function settingsUpdated(array $before, array $after, array $changedKeys): void
    {
        $changedBefore = array_intersect_key($before, array_flip($changedKeys));
        $changedAfter = array_intersect_key($after, array_flip($changedKeys));

        $this->record([
            'module' => 'settings',
            'action' => 'settings_updated',
            'result' => 'success',
            'subject_label' => __('audit.modules.settings'),
            'before' => $changedBefore,
            'after' => $changedAfter,
            'metadata' => [
                'setting_keys_changed' => $changedKeys,
            ],
            'description' => __(
                'audit.descriptions.settings_updated',
                ['count' => count($changedKeys)],
            ),
        ]);
    }

    public function passwordChanged(User $user, string $actorType = 'user'): void
    {
        $actor = $actorType === 'anonymous'
            ? $this->explicitActor(null, 'anonymous', null, null)
            : $this->actor();

        $this->record([
            ...$actor,
            'module' => 'users',
            'action' => 'password_changed',
            'result' => 'success',
            'subject_type' => User::class,
            'subject_id' => (string) $user->getKey(),
            'subject_label' => $user->name,
            'description' => __('audit.descriptions.password_changed'),
        ]);
    }

    public function loginSucceeded(User $user): void
    {
        $this->record([
            ...$this->explicitActor(
                (int) $user->getKey(),
                'user',
                $user->name,
                $user->email,
            ),
            'module' => 'users',
            'action' => 'login_succeeded',
            'result' => 'success',
            'subject_type' => User::class,
            'subject_id' => (string) $user->getKey(),
            'subject_label' => $user->name,
            'description' => __('audit.descriptions.login_succeeded'),
        ]);
    }

    public function loginFailed(?string $email): void
    {
        $this->record([
            ...$this->explicitActor(null, 'anonymous', null, $email),
            'module' => 'users',
            'action' => 'login_failed',
            'result' => 'failure',
            'description' => __('audit.descriptions.login_failed'),
        ]);
    }

    public function logout(?User $user): void
    {
        $this->record([
            ...($user === null
                ? $this->explicitActor(null, 'anonymous', null, null)
                : $this->explicitActor(
                    (int) $user->getKey(),
                    'user',
                    $user->name,
                    $user->email,
                )),
            'module' => 'users',
            'action' => 'logout',
            'result' => 'success',
            'subject_type' => $user === null ? null : User::class,
            'subject_id' => $user === null ? null : (string) $user->getKey(),
            'subject_label' => $user?->name,
            'description' => __('audit.descriptions.logout'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function system(
        string $module,
        string $action,
        string $result,
        string $description,
        array $metadata = [],
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?string $subjectLabel = null,
        ?string $idempotencyKey = null,
    ): void {
        $this->record([
            ...$this->explicitActor(null, 'system', __('audit.actors.system'), null),
            'module' => $module,
            'action' => $action,
            'result' => $result,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'metadata' => $metadata,
            'description' => $description,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function record(array $payload): void
    {
        $this->recorder->handle([
            ...$this->actor(),
            ...$this->context(),
            'occurred_at' => Carbon::now(),
            ...$payload,
        ]);
    }

    /**
     * @return array{actor_id: int|null, actor_type: string, actor_name: string|null, actor_email: string|null}
     */
    private function actor(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return $this->explicitActor(null, 'anonymous', null, null);
        }

        return $this->explicitActor(
            (int) $user->getKey(),
            'user',
            $user->name,
            $user->email,
        );
    }

    /**
     * @return array{actor_id: int|null, actor_type: string, actor_name: string|null, actor_email: string|null}
     */
    private function explicitActor(
        ?int $id,
        string $type,
        ?string $name,
        ?string $email,
    ): array {
        return [
            'actor_id' => $id,
            'actor_type' => $type,
            'actor_name' => $name,
            'actor_email' => $email,
        ];
    }

    /**
     * @return array{request_id: string|null, ip_address: string|null, user_agent: string|null}
     */
    private function context(): array
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return ['request_id' => null, 'ip_address' => null, 'user_agent' => null];
        }

        $request = request();
        $requestId = $request->attributes->get('request_id');
        $userAgent = $request->userAgent();
        $maxLength = (int) config('audit.user_agent_max_length', 500);

        return [
            'request_id' => is_string($requestId) ? $requestId : null,
            'ip_address' => $request->ip(),
            'user_agent' => is_string($userAgent) && trim($userAgent) !== ''
                ? Str::limit($userAgent, $maxLength)
                : null,
        ];
    }

    private function describe(string $module, string $action, ?string $label): string
    {
        $key = "audit.descriptions.{$action}";

        if (Lang::has($key)) {
            return (string) __($key);
        }

        $moduleLabel = (string) __("audit.modules.{$module}");
        $verb = (string) __("audit.verbs.{$action}");

        return $label === null
            ? "{$moduleLabel} {$verb}."
            : "{$moduleLabel} \"{$label}\" {$verb}.";
    }

    private function label(Model $subject): ?string
    {
        $attributes = $subject->getAttributes();

        foreach (['title', 'name', 'original_name'] as $field) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }

            $value = $subject->getAttribute($field);

            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 255);
            }
        }

        return null;
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
                $result[$key] = $this->normalize($item);
            }

            return $result;
        }

        if (is_object($value)) {
            return null;
        }

        return $value;
    }
}
