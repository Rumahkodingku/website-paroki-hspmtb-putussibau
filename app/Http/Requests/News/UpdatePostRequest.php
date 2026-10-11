<?php

namespace App\Http\Requests\News;

use App\Concerns\PostValidationRules;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates updating an article.
 *
 * Identical to the store request apart from two things: the bound article is
 * threaded into the rules so it keeps its own slug, and it is excluded from the
 * pinned count so re-saving a pinned article without touching the box does not
 * find itself in the way.
 *
 * @see docs/DECISIONS.md D-43
 */
class UpdatePostRequest extends FormRequest
{
    use PostValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->postRecord()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalisePostInput();
    }

    public function withValidator(Validator $validator): void
    {
        $this->rejectThirdPinnedPost($validator, $this->postRecord());
        $this->requireFuturePublicationForScheduled($validator);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->postRules($this->postRecord());
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

    /**
     * The bound record, typed for PHPStan.
     *
     * Named postRecord rather than post because Request already has a public
     * post($key, $default): narrowing that to a private no-argument method
     * makes the class fail to load.
     */
    private function postRecord(): Post
    {
        /** @var Post $post */
        $post = $this->route('post');

        return $post;
    }
}
