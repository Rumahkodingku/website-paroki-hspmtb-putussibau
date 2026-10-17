<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->sentence(4));

        return [
            'document_category_id' => DocumentCategory::factory(),
            'title' => $title,
            'description' => fake()->sentence(12),
            'file_disk' => 'local',
            'file_path' => 'documents/'.Str::uuid()->toString().'.pdf',
            'original_filename' => Str::slug($title).'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1024, 5_000_000),
            'version_label' => null,
            'document_date' => null,
            'expires_at' => null,
            'status' => DocumentStatus::Draft,
            'is_featured' => false,
            'published_at' => null,
            'uploaded_by' => null,
            'updated_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => DocumentStatus::Published,
            'published_at' => Carbon::now()->subHour(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => DocumentStatus::Archived,
            'published_at' => Carbon::now()->subMonth(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => Carbon::now()->subDay()->toDateString(),
        ]);
    }
}
