<?php

namespace App\Http\Controllers\Backup;

use App\Actions\Backup\CreateBackupAction;
use App\Actions\Backup\DeleteBackupAction;
use App\Concerns\SortsAdminList;
use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Exceptions\BackupException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\StoreBackupRequest;
use App\Http\Resources\Backup\BackupListResource;
use App\Http\Resources\Backup\BackupResource;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Services\Backup\BackupHealth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    use SortsAdminList;

    public function index(Request $request, BackupHealth $health): Response
    {
        Gate::authorize('viewAny', Backup::class);

        $filters = [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'source' => $request->string('source')->toString(),
            'status' => $request->string('status')->toString(),
            'validasi' => $request->string('validasi')->toString(),
            'dari' => $request->string('dari')->toString(),
            'sampai' => $request->string('sampai')->toString(),
            'sort' => $request->string('sort')->toString(),
            'direction' => $request->string('direction')->toString(),
        ];

        $query = Backup::query()
            ->when($filters['search'] !== '', fn (Builder $query): Builder => $query
                ->where(fn (Builder $inner): Builder => $inner
                    ->where('original_filename', 'like', '%'.$filters['search'].'%')
                    ->orWhere('note', 'like', '%'.$filters['search'].'%')
                    ->orWhere('uuid', 'like', '%'.$filters['search'].'%')))
            ->when(
                in_array($filters['type'], BackupType::values(), true),
                fn (Builder $query): Builder => $query->where('type', $filters['type'])
            )
            ->when(
                in_array($filters['source'], array_column(BackupSource::cases(), 'value'), true),
                fn (Builder $query): Builder => $query->where('source', $filters['source'])
            )
            ->when(
                in_array($filters['status'], BackupStatus::values(), true),
                fn (Builder $query): Builder => $query->where('status', $filters['status'])
            )
            ->when(
                in_array($filters['validasi'], array_column(BackupValidationStatus::cases(), 'value'), true),
                fn (Builder $query): Builder => $query->where('validation_status', $filters['validasi'])
            )
            ->when($filters['dari'] !== '', fn (Builder $query): Builder => $query
                ->whereDate('created_at', '>=', $filters['dari']))
            ->when($filters['sampai'] !== '', fn (Builder $query): Builder => $query
                ->whereDate('created_at', '<=', $filters['sampai']));

        $backups = $this->applyAdminSort(
            $query,
            $request,
            [
                'created_at' => 'created_at',
                'file_size' => 'file_size',
                'type' => 'type',
                'status' => 'status',
            ],
            [['created_at', 'desc']],
        )->paginate($this->perPage($request))->withQueryString();

        $lastBackup = Backup::query()->latestFirst()->first();
        $lastSuccess = Backup::query()->available()->latestFirst()->first();
        $activeRestore = BackupRestore::query()->active()->latest('id')->first();

        return Inertia::render('admin/backup', [
            'backups' => BackupListResource::collection($backups),
            'filters' => $filters,
            'summary' => [
                'total' => Backup::query()->count(),
                'last_backup' => $lastBackup === null ? null : BackupListResource::make($lastBackup)->resolve(),
                'last_success' => $lastSuccess === null ? null : BackupListResource::make($lastSuccess)->resolve(),
                'active_restore' => $activeRestore === null ? null : [
                    'id' => $activeRestore->getKey(),
                    'uuid' => $activeRestore->uuid,
                    'status' => $activeRestore->status->value,
                    'status_label' => $activeRestore->status->label(),
                    'backup_id' => $activeRestore->backup_id,
                ],
            ],
            'readiness' => $health->readiness(),
        ]);
    }

    public function show(Backup $backup, BackupHealth $health): Response
    {
        Gate::authorize('view', $backup);

        $backup->load([
            'restores' => fn ($query) => $query->latest('id')->with('safetyBackup'),
        ]);

        return Inertia::render('admin/backup/show', [
            'backup' => BackupResource::make($backup)->resolve(),
            'readiness' => $health->readiness(),
        ]);
    }

    public function store(
        StoreBackupRequest $request,
        CreateBackupAction $action,
    ): RedirectResponse {
        try {
            $backup = $action->handle(
                $request->backupType(),
                $request->note(),
                $request->user(),
            );

            Inertia::flash('toast', ['type' => 'success', 'message' => __('backups.messages.created')]);

            return to_route('backups.show', $backup);
        } catch (BackupException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->safeMessage()]);

            return back()->withErrors(['backup' => $exception->safeMessage()]);
        }
    }

    public function destroy(
        Backup $backup,
        DeleteBackupAction $action,
    ): RedirectResponse {
        Gate::authorize('delete', $backup);

        try {
            $action->handle($backup);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('backups.messages.deleted')]);

            return to_route('backups.index');
        } catch (BackupException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->safeMessage()]);

            return back()->withErrors(['backup' => $exception->safeMessage()]);
        }
    }
}
