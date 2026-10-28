<?php

namespace App\Services\Backup;

use App\Enums\BackupSource;
use App\Enums\BackupType;
use App\Models\Backup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Decides which backups are old enough to delete.
 *
 * Manual backups are never selected. Safety backups are protected until their
 * `protected_until` passes, and anything referenced by an active restore is
 * excluded by the query.
 */
final class BackupRetention
{
    public function __construct(private readonly BackupSettingsService $settings) {}

    /**
     * @return Builder<Backup>
     */
    public function queryEligible(): Builder
    {
        $values = $this->settings->values();
        $now = Carbon::now();

        return Backup::query()
            ->where(function (Builder $query) use ($values, $now): void {
                $query
                    ->where(function (Builder $query) use ($values, $now): void {
                        $query->where('source', BackupSource::Scheduled)
                            ->where('type', BackupType::Database)
                            ->where('created_at', '<', $now->copy()->subDays((int) $values['retention_daily_days']));
                    })
                    ->orWhere(function (Builder $query) use ($values, $now): void {
                        $query->where('source', BackupSource::Scheduled)
                            ->where('type', BackupType::Full)
                            ->where('created_at', '<', $now->copy()->subWeeks((int) $values['retention_weekly_weeks']));
                    })
                    ->orWhere(function (Builder $query) use ($values, $now): void {
                        $query->where('source', BackupSource::Uploaded)
                            ->where('status', 'failed')
                            ->where('created_at', '<', $now->copy()->subDays((int) $values['retention_invalid_upload_days']));
                    })
                    ->orWhere(function (Builder $query) use ($now): void {
                        $query->where('source', BackupSource::PreRestore)
                            ->where(function (Builder $query) use ($now): void {
                                $query->whereNull('protected_until')
                                    ->orWhere('protected_until', '<', $now);
                            });
                    });
            })
            ->whereDoesntHave('restores', fn (Builder $query): Builder => $query->active());
    }

    public function eligible(Backup $backup, ?Carbon $now = null): bool
    {
        $now ??= Carbon::now();
        $values = $this->settings->values();

        if ($backup->restores()->active()->exists()) {
            return false;
        }

        return match ($backup->source) {
            BackupSource::Manual => false,
            BackupSource::Scheduled => $backup->type === BackupType::Database
                ? $backup->created_at->lt($now->copy()->subDays((int) $values['retention_daily_days']))
                : $backup->created_at->lt($now->copy()->subWeeks((int) $values['retention_weekly_weeks'])),
            BackupSource::Uploaded => $backup->status->value === 'failed'
                && $backup->created_at->lt($now->copy()->subDays((int) $values['retention_invalid_upload_days'])),
            BackupSource::PreRestore => ! $backup->isProtected(),
        };
    }
}
