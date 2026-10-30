<?php

namespace App\Http\Controllers\Installation;

use App\Enums\InstallationError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Installation\RunInstallationRequest;
use App\Services\Installation\InstallationRunner;
use App\Services\Installation\InstallationState;
use Illuminate\Http\JsonResponse;

/**
 * The one endpoint that changes the database, plus the status the browser
 * polls while it waits.
 *
 * JSON rather than an Inertia visit on purpose: the install can take a while,
 * a browser that leaves should not lose the outcome, and 409/422 are part of
 * the contract the specification fixes. Progress is read from the backend
 * state file, never from anything the browser sends.
 *
 * @see docs/DECISIONS.md D-92
 */
final class InstallationRunController extends Controller
{
    public function __construct(
        private readonly InstallationState $state,
        private readonly InstallationRunner $runner,
    ) {}

    public function run(RunInstallationRequest $request): JsonResponse
    {
        $status = $this->state->status();

        if ($status === InstallationState::COMPLETED) {
            return response()->json(['status' => InstallationState::COMPLETED], 409);
        }

        if ($status === InstallationState::INSTALLING) {
            return response()->json([
                'status' => InstallationState::INSTALLING,
                'error_code' => InstallationError::InstallLocked->value,
                'message' => InstallationError::InstallLocked->message(),
            ], 409);
        }

        if ($status === InstallationState::FAILED) {
            $code = $this->state->errorCode() ?? InstallationError::InstallFailed->value;

            return response()->json([
                'status' => InstallationState::FAILED,
                'error_code' => $code,
                'message' => InstallationError::tryFrom($code)?->message() ?? InstallationError::InstallFailed->message(),
            ], 409);
        }

        $admin = session('installation.super_admin');
        $application = session('installation.application');
        $database = session('installation.database');
        $email = session('installation.email');

        if (! is_array($admin) || ! is_array($application) || ! is_array($database) || ! is_array($email)) {
            return response()->json([
                'status' => InstallationState::FAILED,
                'error_code' => InstallationError::SessionExpired->value,
                'message' => InstallationError::SessionExpired->message(),
            ], 409);
        }

        $result = $this->runner->run($admin, $application, $database);

        session()->forget([
            'installation.super_admin',
            'installation.application',
            'installation.database',
            'installation.database_verified_at',
            'installation.email',
        ]);

        if ($result['status'] === InstallationState::COMPLETED) {
            session([
                'installation.completed_notice' => true,
                'installation.completed_summary' => [
                    'name' => is_string($admin['name'] ?? null) ? $admin['name'] : '',
                    'email' => is_string($admin['email'] ?? null) ? $admin['email'] : '',
                ],
            ]);

            return response()->json(['status' => InstallationState::COMPLETED]);
        }

        $code = $result['error_code'] ?? InstallationError::InstallFailed->value;

        return response()->json([
            'status' => InstallationState::FAILED,
            'error_code' => $code,
            'message' => InstallationError::tryFrom($code)?->message() ?? InstallationError::InstallFailed->message(),
        ]);
    }

    public function progress(): JsonResponse
    {
        $payload = $this->state->payload();
        $code = $payload['error_code'];

        return response()->json([
            'status' => $payload['status'],
            'current_step' => $payload['current_step'],
            'started_at' => $payload['started_at'],
            'completed_at' => $payload['completed_at'],
            'failed_at' => $payload['failed_at'],
            'error_code' => $code,
            'message' => is_string($code)
                ? (InstallationError::tryFrom($code)?->message() ?? InstallationError::InstallFailed->message())
                : null,
        ]);
    }
}
