<?php

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

final class TestEmailConnectionRequest extends FormRequest
{
    private const KEYS = [
        'mailer',
        'host',
        'port',
        'username',
        'password',
        'from_address',
        'from_name',
        'to',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (self::KEYS as $key) {
            $value = $this->input($key);

            if (is_string($value)) {
                $value = trim($value);
            }

            $values[$key] = is_string($value) && $value !== '' ? $value : null;
        }

        if (is_string($values['port'] ?? null)) {
            $values['port'] = (int) $values['port'];
        }

        $this->merge($values);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'mailer' => ['required', 'string', 'in:smtp,log'],
            'host' => ['required_if:mailer,smtp', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:mailer,smtp', 'nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'to' => ['required', 'email', 'max:255'],
        ];
    }
}
