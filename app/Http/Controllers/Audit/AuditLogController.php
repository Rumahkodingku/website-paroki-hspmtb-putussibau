<?php

namespace App\Http\Controllers\Audit;

use App\Concerns\SortsAdminList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditLogIndexRequest;
use App\Http\Resources\Audit\AuditEventDetailResource;
use App\Http\Resources\Audit\AuditEventListResource;
use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    use SortsAdminList;

    public function index(AuditLogIndexRequest $request): Response
    {
        $filters = $request->filters();
        $from = $request->fromUtc();
        $to = $request->toUtc();

        $events = AuditEvent::query()
            ->select([
                'id',
                'actor_id',
                'actor_type',
                'actor_name',
                'action',
                'module',
                'subject_id',
                'subject_label',
                'description',
                'result',
                'occurred_at',
            ])
            ->when($filters['search'] !== '', fn (Builder $query) => $query
                ->search($filters['search']))
            ->when($from !== null, fn (Builder $query) => $query
                ->where('occurred_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query
                ->where('occurred_at', '<=', $to))
            ->when($filters['actor'] !== '', fn (Builder $query) => $this->applyActorFilter(
                $query,
                $filters['actor'],
            ))
            ->when(
                in_array($filters['module'], (array) config('audit.modules'), true),
                fn (Builder $query) => $query->forModule($filters['module'])
            )
            ->when(
                in_array($filters['action'], (array) config('audit.actions'), true),
                fn (Builder $query) => $query->forAction($filters['action'])
            )
            ->when(
                in_array($filters['result'], (array) config('audit.results'), true),
                fn (Builder $query) => $query->forResult($filters['result'])
            );

        $events = $this->applyAdminSort(
            $events,
            $request,
            [
                'occurred_at' => 'occurred_at',
                'action' => 'action',
                'module' => 'module',
                'result' => 'result',
                'actor' => 'actor_name',
                'subject_label' => 'subject_label',
            ],
            [['occurred_at', 'desc']],
        )
            ->paginate($request->perPage())
            ->withQueryString();

        return Inertia::render('admin/audit-log', [
            'events' => AuditEventListResource::collection($events),
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function show(AuditEvent $auditEvent): Response
    {
        Gate::authorize('view', $auditEvent);

        return Inertia::render('admin/audit-log/show', [
            'event' => AuditEventDetailResource::make($auditEvent)->resolve(),
        ]);
    }

    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    private function applyActorFilter(Builder $query, string $actor): Builder
    {
        if ($actor === 'system' || $actor === 'anonymous') {
            return $query->forActor($actor);
        }

        if (preg_match('/^user:(\d+)$/', $actor, $matches) === 1) {
            return $query->forActor((int) $matches[1]);
        }

        return $query;
    }

    /**
     * @return array{
     *     modules: list<array{value: string, label: string}>,
     *     actions: list<array{value: string, label: string}>,
     *     results: list<array{value: string, label: string}>,
     *     actors: list<array{value: string, label: string}>
     * }
     */
    private function options(): array
    {
        return [
            'modules' => $this->codeOptions((array) config('audit.modules'), 'modules'),
            'actions' => $this->codeOptions((array) config('audit.actions'), 'actions'),
            'results' => $this->codeOptions((array) config('audit.results'), 'results'),
            'actors' => $this->actorOptions(),
        ];
    }

    /**
     * @param  array<int, mixed>  $codes
     * @return list<array{value: string, label: string}>
     */
    private function codeOptions(array $codes, string $group): array
    {
        $options = [];

        foreach ($codes as $code) {
            if (! is_string($code)) {
                continue;
            }

            $options[] = [
                'value' => $code,
                'label' => (string) __("audit.{$group}.{$code}"),
            ];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function actorOptions(): array
    {
        $rows = AuditEvent::query()
            ->select(['actor_type', 'actor_id', 'actor_name'])
            ->distinct()
            ->orderBy('actor_name')
            ->limit(200)
            ->get();

        $options = [];

        foreach ($rows as $row) {
            $value = match ($row->actor_type) {
                'system' => 'system',
                'anonymous' => 'anonymous',
                default => $row->actor_id === null
                    ? 'anonymous'
                    : 'user:'.$row->actor_id,
            };

            $label = match ($row->actor_type) {
                'system' => (string) __('audit.actors.system'),
                'anonymous' => (string) __('audit.actors.anonymous'),
                default => $row->actor_id === null
                    ? (string) __('audit.actors.anonymous')
                    : (is_string($row->actor_name) && trim($row->actor_name) !== ''
                        ? $row->actor_name
                        : 'Pengguna #'.$row->actor_id),
            };

            $options[$value] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return array_values($options);
    }
}
