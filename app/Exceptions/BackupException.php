<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * A backup failure with a stable, safe code.
 *
 * The UI never receives the previous exception's message. It receives the
 * translated message for `reasonCode`; the technical detail stays in the log.
 */
final class BackupException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        ?Throwable $previous = null,
    ) {
        parent::__construct($reasonCode, 0, $previous);
    }

    public static function because(string $reasonCode, ?Throwable $previous = null): self
    {
        return new self($reasonCode, $previous);
    }

    public static function from(Throwable $exception): self
    {
        if ($exception instanceof self) {
            return $exception;
        }

        return new self('backup_failed', $exception);
    }

    public function safeMessage(): string
    {
        $key = 'backups.errors.'.$this->reasonCode;
        $message = __($key);

        return is_string($message) && $message !== $key
            ? $message
            : (string) __('backups.errors.backup_failed');
    }
}
