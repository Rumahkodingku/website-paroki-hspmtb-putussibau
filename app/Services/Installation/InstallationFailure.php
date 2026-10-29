<?php

namespace App\Services\Installation;

use App\Enums\InstallationError;
use RuntimeException;

/**
 * A controlled installation failure that already carries its safe error code.
 */
final class InstallationFailure extends RuntimeException
{
    public function __construct(public readonly InstallationError $error)
    {
        parent::__construct($error->value);
    }
}
