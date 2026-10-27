<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Logout $event): void
    {
        $this->audit->logout(
            $event->user instanceof User ? $event->user : null,
        );
    }
}
