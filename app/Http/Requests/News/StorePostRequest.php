<?php

namespace App\Http\Requests\News;

use App\Concerns\PostValidationRules;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates creating an article.
 *
 * Two things happen before the rules run, in this order and both in
 * prepareForValidation: the body is sanitized, and the publication date is read
 * as Asia/Pontianak and converted to UTC (D-01). Neither can be a rule — a rule
 * rejects a value, it cannot rewrite one — and the body in particular has to be
 * cleaned on the way in because the editor being in the browser says nothing
 * about the request body (PRD D-14).
 *
 * @see docs/DECISIONS.md D-25, D-43
 */
class StorePostRequest extends FormRequest
{
    use PostValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalisePostInput();
    }

    public function withValidator(Validator $validator): void
    {
        $this->rejectThirdPinnedPost($validator);
        $this->requireFuturePublicationForScheduled($validator);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->postRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->postMessages();
    }

    /**
     * @return array<string, mixed>
     */
    public function postPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->postAttributes($validated);
    }
}
