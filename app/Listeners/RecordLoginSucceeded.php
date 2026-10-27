<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class RecordLoginSucceeded
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->loginSucceeded($event->user);
        }
    }
}
