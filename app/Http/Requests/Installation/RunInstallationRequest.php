<?php

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The explicit confirmation Step 6 requires.
 *
 * A GET request can never install anything; this request only exists on the
 * POST that runs the installation, and `accepted` means the browser had to
 * tick something rather than merely load the review screen.
 */
final class RunInstallationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'accepted'],
        ];
    }
}
