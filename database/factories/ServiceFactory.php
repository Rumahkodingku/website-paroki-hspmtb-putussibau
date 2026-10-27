<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 *
 * The default is an inactive service with no Google Form, because that is the
 * state a service is actually created in (SVC-01): content waits for the parish
 * to validate it. Every other case is a named state, so a test that says what it
 * means does not have to know the default.
 *
 * @see docs/DECISIONS.md D-54
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // word() is typed as string, unlike words(), whose signature allows
        // array|string depending on its second argument.
        $title = Str::title(
            fake()->unique()->word().' '.fake()->word().' '.fake()->word(),
        );

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'service_category_id' => ServiceCategory::factory(),
            'thumbnail_media_id' => null,
            'thumbnail_alt' => null,
            'summary' => fake()->sentence(12),
            'introduction' => '<p>'.fake()->paragraph(2).'</p>',
            'audience_info' => null,
            'preparation_info' => null,
            'schedule_info' => null,
            'fee_info' => null,
            'contact_name' => null,
            'contact_phone' => null,
            'form_url' => null,
            'form_button_label' => 'Daftar Sekarang',
            'is_form_open' => false,
            'form_closed_message' => null,
            'is_emergency' => false,
            // SVC-01: a new service is never public until it is activated.
            'is_active' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * A service the parish has activated: visible on the public site.
     */
    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }

    /**
     * A service with an open Google Form.
     */
    public function withForm(string $url = 'https://docs.google.com/forms/d/e/contoh/viewform'): static
    {
        return $this->state(fn (): array => [
            'form_url' => $url,
            'is_form_open' => true,
        ]);
    }

    /**
     * A service with a form that has been closed.
     */
    public function formClosed(string $url = 'https://docs.google.com/forms/d/e/contoh/viewform'): static
    {
        return $this->state(fn (): array => [
            'form_url' => $url,
            'is_form_open' => false,
            'form_closed_message' => 'Pendaftaran sedang ditutup.',
        ]);
    }

    /**
     * A direct-contact emergency service. It never uses a form and cannot be
     * activated without a contact number (SVC-06).
     */
    public function emergency(string $phone = '628123456789'): static
    {
        return $this->state(fn (): array => [
            'is_emergency' => true,
            'contact_name' => 'Petugas Pelayanan',
            'contact_phone' => $phone,
            'form_url' => null,
            'is_form_open' => false,
        ]);
    }
}
