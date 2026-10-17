<?php

namespace App\Concerns;

use App\Enums\DocumentStatus;

trait DocumentFormMeta
{
    /**
     * @return array<string, mixed>
     */
    protected function documentFormMeta(): array
    {
        return [
            'labels' => (array) __('documents.labels'),
            'hints' => (array) __('documents.hints'),
            'copy' => (array) __('documents.admin'),
            'empty' => (array) __('documents.empty'),
            'statuses' => collect(DocumentStatus::cases())
                ->map(fn (DocumentStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values()
                ->all(),
        ];
    }
}
