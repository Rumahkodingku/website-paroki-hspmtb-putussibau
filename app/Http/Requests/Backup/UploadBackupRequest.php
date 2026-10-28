<?php

namespace App\Http\Requests\Backup;

use App\Models\Backup;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UploadBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('upload', Backup::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKilobytes = (int) floor(
            app(BackupSettingsService::class)->uploadLimitBytes() / 1024
        );

        return [
            'file' => [
                'required',
                'file',
                'max:'.$maxKilobytes,
                'mimetypes:application/gzip,application/x-gzip,application/zip,application/x-zip-compressed',
                'extensions:gz,zip',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih arsip backup terlebih dahulu.',
            'file.file' => 'Unggahan bukan berkas yang valid.',
            'file.max' => 'Ukuran arsip melebihi batas server.',
            'file.mimetypes' => 'Format arsip tidak didukung.',
            'file.extensions' => 'Arsip harus berupa .sql.gz atau .zip.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $file = $this->file('file');

                if ($file === null) {
                    return;
                }

                $name = strtolower($file->getClientOriginalName());

                if (! str_ends_with($name, '.sql.gz') && ! str_ends_with($name, '.zip')) {
                    $validator->errors()->add('file', 'Arsip harus berupa .sql.gz atau .zip.');
                }
            },
        ];
    }
}
