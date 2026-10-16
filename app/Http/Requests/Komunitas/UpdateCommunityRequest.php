<?php

namespace App\Http\Requests\Komunitas;

use App\Concerns\CommunityValidationRules;
use App\Models\Community;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-55
 */
class UpdateCommunityRequest extends FormRequest
{
    use CommunityValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->community()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCommunityInput();
    }

    /**
     * @return array<string, array<int, \Closure|ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->communityRules($this->community());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->communityMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->communityAttributes();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [$this->communityBusinessRules($this->community())];
    }

    private function community(): Community
    {
        /** @var Community $community */
        $community = $this->route('community');

        return $community;
    }
}
