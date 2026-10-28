<?php

namespace App\Services\Backup;

use App\Exceptions\BackupException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs an external database binary with an argument array.
 *
 * No shell string is ever built. The password travels through the environment,
 * not through argv. Tests replace this class with a fake to exercise the dump
 * and restore orchestration without touching a real database.
 */
class CommandRunner
{
    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $environment
     * @return array{exit_code: int, stdout: string, stderr: string}
     */
    public function run(
        array $command,
        array $environment = [],
        int $timeout = 0,
        ?string $inputPath = null,
    ): array {
        $binary = $command[0] ?? '';

        if ($binary === '' || (new ExecutableFinder)->find($binary) === null) {
            throw BackupException::because('binary_missing');
        }

        $process = new Process($command, base_path(), $environment, null, $timeout);
        $input = null;

        if ($inputPath !== null) {
            $input = fopen($inputPath, 'rb');

            if ($input === false) {
                throw BackupException::because('storage_unavailable');
            }

            $process->setInput($input);
        }

        try {
            $process->run();
        } finally {
            if (is_resource($input)) {
                fclose($input);
            }
        }

        return [
            'exit_code' => $process->getExitCode() ?? -1,
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
        ];
    }
}
