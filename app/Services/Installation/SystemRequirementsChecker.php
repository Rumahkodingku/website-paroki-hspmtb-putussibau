<?php

namespace App\Services\Installation;

/**
 * Read-only checks of the machine the application is about to run on.
 *
 * Everything here must be safe to call before the database exists: no queries,
 * no writes, and no absolute paths in the response. A failure blocks the
 * wizard; a warning does not.
 *
 * @see docs/DECISIONS.md D-94
 */
final class SystemRequirementsChecker
{
    /**
     * @return list<array{key: string, label: string, status: string, detail: string, action: string|null}>
     */
    public function checks(): array
    {
        $checks = [];

        $minimumPhp = (string) config('installer.requirements.minimum_php');
        $checks[] = $this->row(
            'php',
            'Versi PHP',
            version_compare(PHP_VERSION, $minimumPhp, '>=') ? 'pass' : 'fail',
            'Terdeteksi PHP '.PHP_VERSION.'.',
            'Tingkatkan PHP ke '.$minimumPhp.' atau lebih baru.',
        );

        $checks[] = $this->extensionRow(
            'extensions',
            'Ekstensi PHP wajib',
            (array) config('installer.requirements.extensions', []),
            'fail',
        );

        $checks[] = $this->extensionRow(
            'extensions_optional',
            'Ekstensi PHP opsional',
            (array) config('installer.requirements.warning_extensions', []),
            'warning',
        );

        $checks[] = $this->row(
            'app_key',
            'Application key',
            filled(config('app.key')) ? 'pass' : 'fail',
            filled(config('app.key')) ? 'APP_KEY sudah terisi.' : 'APP_KEY belum terisi.',
            'Jalankan php artisan key:generate pada server.',
        );

        $paths = (array) config('installer.requirements.paths', []);

        $checks[] = $this->writableRow(
            'storage_writable',
            'Direktori storage',
            (string) ($paths['storage'] ?? storage_path()),
            'Pastikan direktori storage dapat ditulis oleh pengguna web server.',
        );

        $checks[] = $this->writableRow(
            'cache_writable',
            'Direktori bootstrap/cache',
            (string) ($paths['cache'] ?? base_path('bootstrap/cache')),
            'Pastikan direktori bootstrap/cache dapat ditulis oleh pengguna web server.',
        );

        $hasAssets = is_file((string) ($paths['manifest'] ?? public_path('build/manifest.json')))
            || is_file((string) ($paths['hot'] ?? public_path('hot')));

        $checks[] = $this->row(
            'frontend_assets',
            'Aset frontend',
            $hasAssets ? 'pass' : 'fail',
            $hasAssets ? 'Manifest atau Vite dev server terdeteksi.' : 'Aset frontend belum dibangun.',
            'Jalankan npm run build atau npm run dev pada server.',
        );

        $hasStorageLink = file_exists((string) ($paths['storage_link'] ?? public_path('storage')));

        $checks[] = $this->row(
            'storage_link',
            'Tautan storage publik',
            $hasStorageLink ? 'pass' : 'warning',
            $hasStorageLink ? 'public/storage tersedia.' : 'public/storage belum dibuat; media tidak akan tampil.',
            'Jalankan php artisan storage:link.',
        );

        $executionTime = (int) ini_get('max_execution_time');
        $minimumExecutionTime = (int) config('installer.requirements.minimum_execution_time', 120);
        $timeIsEnough = $executionTime === 0 || $executionTime >= $minimumExecutionTime;

        $checks[] = $this->row(
            'execution_time',
            'Batas waktu eksekusi',
            $timeIsEnough ? 'pass' : 'warning',
            $executionTime === 0
                ? 'Tanpa batas waktu.'
                : 'Batas waktu '.$executionTime.' detik.',
            'Naikkan max_execution_time ke '.$minimumExecutionTime.' detik atau lebih.',
        );

        $debugInProduction = app()->isProduction() && (bool) config('app.debug');

        $checks[] = $this->row(
            'debug',
            'Mode debug',
            $debugInProduction ? 'warning' : 'pass',
            $debugInProduction ? 'APP_DEBUG aktif pada environment production.' : 'Mode debug aman.',
            'Setel APP_DEBUG=false untuk production.',
        );

        $url = (string) config('app.url');
        $urlIsAbsolute = filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);

        $checks[] = $this->row(
            'app_url',
            'URL aplikasi',
            $urlIsAbsolute ? 'pass' : 'fail',
            $urlIsAbsolute ? 'APP_URL berupa URL absolut.' : 'APP_URL bukan URL absolut yang valid.',
            'Setel APP_URL, misalnya https://paroki.example.org.',
        );

        $driver = (string) config('database.default');
        $driverSupported = in_array($driver, (array) config('installer.database.supported_drivers', []), true);

        $checks[] = $this->row(
            'database_driver',
            'Driver database',
            $driverSupported ? 'pass' : 'fail',
            'Driver terdeteksi: '.$driver.'.',
            'Gunakan DB_CONNECTION=mysql.',
        );

        return $checks;
    }

    public function passes(): bool
    {
        foreach ($this->checks() as $check) {
            if ($check['status'] === 'fail') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  'pass'|'warning'|'fail'  $status
     * @return array{key: string, label: string, status: 'pass'|'warning'|'fail', detail: string, action: string|null}
     */
    private function row(string $key, string $label, string $status, string $detail, ?string $action): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'detail' => $detail,
            'action' => $status === 'pass' ? null : $action,
        ];
    }

    /**
     * @param  array<mixed>  $extensions
     * @param  'pass'|'warning'|'fail'  $missingStatus
     * @return array{key: string, label: string, status: 'pass'|'warning'|'fail', detail: string, action: string|null}
     */
    private function extensionRow(string $key, string $label, array $extensions, string $missingStatus): array
    {
        $missing = [];

        foreach ($extensions as $extension) {
            if (is_string($extension) && ! extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        if ($missing === []) {
            return $this->row($key, $label, 'pass', 'Semua ekstensi tersedia.', null);
        }

        return $this->row(
            $key,
            $label,
            $missingStatus,
            'Hilang: '.implode(', ', $missing).'.',
            'Pasang ekstensi PHP: '.implode(', ', $missing).'.',
        );
    }

    /**
     * @return array{key: string, label: string, status: string, detail: string, action: string|null}
     */
    private function writableRow(string $key, string $label, string $path, string $action): array
    {
        $writable = is_dir($path) && is_writable($path);

        return $this->row(
            $key,
            $label,
            $writable ? 'pass' : 'fail',
            $writable ? 'Direktori dapat ditulis.' : 'Direktori tidak dapat ditulis.',
            $action,
        );
    }
}
