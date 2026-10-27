<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;

class RecordLoginFailed
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->audit->loginFailed(
            is_string($email) && trim($email) !== '' ? $email : null,
        );
    }
}
