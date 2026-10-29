<?php

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TestDatabaseConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'driver' => is_string($this->input('driver')) ? trim($this->input('driver')) : $this->input('driver'),
            'host' => is_string($this->input('host')) ? trim($this->input('host')) : $this->input('host'),
            'database' => is_string($this->input('database')) ? trim($this->input('database')) : $this->input('database'),
            'username' => is_string($this->input('username')) ? trim($this->input('username')) : $this->input('username'),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'driver' => ['required', 'string', Rule::in((array) config('installer.database.supported_drivers', []))],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
        ];
    }
}
