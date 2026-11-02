<?php

namespace App\Concerns;

use App\Enums\DocumentStatus;

trait DocumentFormMeta
{
    use BuildsFormMeta;

    /**
     * @return array<string, mixed>
     */
    protected function documentFormMeta(): array
    {
        return $this->buildFormMeta('documents', [
            'statuses' => collect(DocumentStatus::cases())
                ->map(fn (DocumentStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
        ]);
    }
}
