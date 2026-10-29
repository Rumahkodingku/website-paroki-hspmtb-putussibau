<?php

namespace App\Services\Installation;

/**
 * Safely updates selected keys in the application's .env file.
 *
 * The write is atomic (temp file + rename) and a backup is created once
 * before the first modification. Values are quoted when they contain
 * whitespace or special characters.
 */
class EnvironmentWriter
{
    /**
     * @param  array<string, string|null>  $values
     */
    public function write(array $values): bool
    {
        $path = base_path('.env');

        if (! is_file($path) || ! is_writable($path)) {
            return false;
        }

        $backup = base_path('.env.pre-install');

        if (! is_file($backup)) {
            if (! @copy($path, $backup)) {
                return false;
            }
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatValue((string) $value);

            if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $contents)) {
                $contents = (string) preg_replace(
                    '/^'.preg_quote($key, '/').'=.*/m',
                    $line,
                    $contents,
                );
            } else {
                $contents = rtrim($contents)."\n".$line."\n";
            }
        }

        $temporary = $path.'.tmp';

        if (@file_put_contents($temporary, $contents) === false) {
            return false;
        }

        if (! @rename($temporary, $path)) {
            @unlink($temporary);

            return false;
        }

        return true;
    }

    private function formatValue(string $value): string
    {
        if ($value === '' || preg_match('/\s|#|=/', $value)) {
            return '"'.addslashes($value).'"';
        }

        return $value;
    }
}
