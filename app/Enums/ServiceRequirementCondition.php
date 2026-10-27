<?php

namespace App\Enums;

enum ServiceRequirementCondition: string
{
    case Required = 'required';
    case Conditional = 'conditional';
    case UponRequest = 'upon_request';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return (string) __('pelayanan.requirement_conditions.'.$this->value);
    }
}
