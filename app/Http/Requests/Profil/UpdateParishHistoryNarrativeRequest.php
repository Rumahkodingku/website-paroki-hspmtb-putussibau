<?php

namespace App\Http\Requests\Profil;

use App\Concerns\ProfilValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the history narrative singleton on the Sejarah admin page.
 *
 * The narrative moved here from the removed Informasi Profil module (D-69).
 * The rich text is sanitized before the rules run, the same way every other
 * Profil rich-text field is.
 *
 * Authorization lives in the controller with Gate::authorize, following the
 * other Sejarah actions which use a policy.
 *
 * @see docs/DECISIONS.md D-69
 */
class UpdateParishHistoryNarrativeRequest extends FormRequest
{
    use ProfilValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('parishhistories.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeHtmlFields(['narrative']);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'narrative' => ['nullable', 'string', 'max:500000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'narrative.max' => 'Narasi sejarah terlalu panjang untuk disimpan.',
        ];
    }
}
