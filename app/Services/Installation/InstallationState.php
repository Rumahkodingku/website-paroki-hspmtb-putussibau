<?php

namespace App\Services\Installation;

use App\Enums\InstallationError;
use JsonException;
use RuntimeException;

/**
 * The installer's state, stored in one small file.
 *
 * A table cannot be the source of truth here: before the first migration there
 * is no table to write to, and the wizard has to work on an empty database.
 * The file is private (storage/app/private), contains no secrets, and is
 * written atomically so a half-written JSON document can never be read back as
 * a valid state.
 *
 * Anything unreadable fails closed: the status becomes failed with
 * STATUS_UNREADABLE rather than being treated as a fresh install.
 *
 * @see docs/DECISIONS.md D-91
 */
final class InstallationState
{
    public const PENDING = 'pending';

    public const INSTALLING = 'installing';

    public const FAILED = 'failed';

    public const COMPLETED = 'completed';

    /**
     * @var list<string>
     */
    private const STATUSES = [self::PENDING, self::INSTALLING, self::FAILED, self::COMPLETED];

    /**
     * @return array{version: int, status: string, current_step: string|null, started_at: string|null, completed_at: string|null, failed_at: string|null, error_code: string|null}
     */
    public function payload(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return $this->defaults();
        }

        $contents = @file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return $this->unreadable();
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->unreadable();
        }

        if (! is_array($decoded) || ! in_array($decoded['status'] ?? null, self::STATUSES, true)) {
            return $this->unreadable();
        }

        return array_merge($this->defaults(), $decoded);
    }

    public function status(): string
    {
        return (string) $this->payload()['status'];
    }

    public function currentStep(): ?string
    {
        $step = $this->payload()['current_step'];

        return is_string($step) ? $step : null;
    }

    public function isCompleted(): bool
    {
        return $this->status() === self::COMPLETED;
    }

    public function isInstalling(): bool
    {
        return $this->status() === self::INSTALLING;
    }

    public function isFailed(): bool
    {
        return $this->status() === self::FAILED;
    }

    public function errorCode(): ?string
    {
        $code = $this->payload()['error_code'];

        return is_string($code) ? $code : null;
    }

    public function begin(): void
    {
        $this->write([
            ...$this->defaults(),
            'status' => self::INSTALLING,
            'current_step' => 'validate',
            'started_at' => $this->timestamp(),
        ]);
    }

    public function step(string $step): void
    {
        $payload = $this->payload();

        if ($payload['status'] !== self::INSTALLING) {
            throw new RuntimeException('Tidak dapat mencatat langkah instalasi saat status bukan installing.');
        }

        $payload['current_step'] = $step;

        $this->write($payload);
    }

    public function fail(InstallationError $error): void
    {
        $payload = $this->payload();

        $payload['status'] = self::FAILED;
        $payload['failed_at'] = $this->timestamp();
        $payload['error_code'] = $error->value;

        $this->write($payload);
    }

    public function complete(): void
    {
        $payload = $this->payload();

        $payload['status'] = self::COMPLETED;
        $payload['current_step'] = 'lock';
        $payload['completed_at'] = $this->timestamp();
        $payload['error_code'] = null;

        $this->write($payload);
    }

    /**
     * Remove the status file. Only an operator does this, over SSH, after
     * verifying the database by hand; it is deliberately not an HTTP action.
     */
    public function forget(): void
    {
        if (is_file($this->path())) {
            @unlink($this->path());
        }
    }

    public function path(): string
    {
        return (string) config('installer.status_path');
    }

    /**
     * @return array{version: int, status: string, current_step: string|null, started_at: string|null, completed_at: string|null, failed_at: string|null, error_code: string|null}
     */
    private function defaults(): array
    {
        return [
            'version' => 1,
            'status' => self::PENDING,
            'current_step' => null,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'error_code' => null,
        ];
    }

    /**
     * @return array{version: int, status: string, current_step: string|null, started_at: string|null, completed_at: string|null, failed_at: string|null, error_code: string|null}
     */
    private function unreadable(): array
    {
        return [
            ...$this->defaults(),
            'status' => self::FAILED,
            'error_code' => InstallationError::StatusUnreadable->value,
        ];
    }

    /**
     * @param  array{version: int, status: string, current_step: string|null, started_at: string|null, completed_at: string|null, failed_at: string|null, error_code: string|null}  $payload
     */
    private function write(array $payload): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Direktori status instalasi tidak dapat dibuat.');
        }

        $temporary = tempnam($directory, 'status-');

        if ($temporary === false) {
            throw new RuntimeException('File sementara status instalasi tidak dapat dibuat.');
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false || file_put_contents($temporary, $json, LOCK_EX) === false) {
            @unlink($temporary);

            throw new RuntimeException('Status instalasi tidak dapat ditulis.');
        }

        @chmod($temporary, 0600);

        if (! @rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException('Status instalasi tidak dapat dipindahkan ke lokasi akhir.');
        }
    }

    private function timestamp(): string
    {
        return now()->toIso8601String();
    }
}
