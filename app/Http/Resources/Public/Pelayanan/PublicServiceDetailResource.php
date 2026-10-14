<?php

namespace App\Http\Resources\Public\Pelayanan;

use App\Models\Service;
use App\Services\HtmlSanitizer;
use App\Services\SiteSettingsService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A service as the public detail page needs it.
 *
 * This is where the four CTA states of PRD 7.6 are decided, in one place rather
 * than spread across a React component:
 *
 *   - emergency -> direct contact, no registration button and no four-step flow,
 *   - form_url present and open -> a live registration button,
 *   - form_url present and closed -> a disabled button plus the closing message,
 *   - no form_url -> a pointer to the secretariat.
 *
 * The contact fallback of roadmap 9.2.2 is also resolved here: the service's own
 * number first, then the parish secretariat from site settings, then nothing. A
 * number that does not normalize produces no tel:/wa.me link at all, so the page
 * never draws a button that goes nowhere.
 *
 * @mixin Service
 */
class PublicServiceDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $contact = $this->resolveContact();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'thumbnail' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('large')),
            'thumbnail_alt' => $this->thumbnail_alt,
            'summary' => $this->summary,
            // Sanitized HTML. Only rendered through RichText.
            'introduction' => $this->introduction,
            'schedule_info' => $this->schedule_info,
            'fee_info' => $this->fee_info,
            'is_emergency' => $this->is_emergency,
            'category' => $this->whenLoaded('serviceCategory', fn (): array => [
                'id' => $this->serviceCategory->getKey(),
                'name' => $this->serviceCategory->name,
                'slug' => $this->serviceCategory->slug,
            ]),
            'cta' => $this->callToAction(),
            'contact' => $contact,
            'requirements' => $this->whenLoaded(
                'requirements',
                fn (): array => PublicServiceRequirementResource::collection($this->requirements)->resolve(),
            ),
            'steps' => $this->whenLoaded(
                'steps',
                fn (): array => PublicServiceStepResource::collection($this->steps)->resolve(),
            ),
            'faqs' => $this->whenLoaded(
                'faqs',
                fn (): array => PublicServiceFaqResource::collection($this->faqs)->resolve(),
            ),
            'updated_at' => $this->updated_at->toIso8601String(),
            'seo' => [
                'title' => $this->title,
                'description' => $this->description(),
            ],
        ];
    }

    /**
     * The registration state, reduced to what the page renders (PRD 7.6).
     *
     * @return array<string, mixed>
     */
    private function callToAction(): array
    {
        $state = match (true) {
            $this->is_emergency => 'emergency',
            $this->usesGoogleForm() && $this->is_form_open => 'form_open',
            $this->usesGoogleForm() => 'form_closed',
            default => 'contact',
        };

        return [
            'state' => $state,
            // A URL is only ever sent for a state that actually opens one.
            'url' => $state === 'form_open' ? $this->form_url : null,
            'label' => $this->form_button_label,
            'message' => $state === 'form_closed' ? $this->form_closed_message : null,
        ];
    }

    /**
     * The contact a visitor may act on, with the links already built.
     *
     * @return array<string, mixed>
     */
    private function resolveContact(): array
    {
        /** @var SiteSettingsService $settings */
        $settings = app(SiteSettingsService::class);

        $settingsPhone = (string) $settings->get('contact_phone');
        $settingsWhatsApp = (string) $settings->get('contact_whatsapp');

        if ($this->hasOwnContact()) {
            $displayPhone = $this->contact_phone;
            $source = 'service';
        } elseif ($settingsPhone !== '') {
            $displayPhone = $settingsPhone;
            $source = 'secretariat';
        } else {
            $displayPhone = null;
            $source = null;
        }

        $whatsAppNumber = $this->hasOwnContact()
            ? $this->contact_phone
            : ($settingsWhatsApp !== '' ? $settingsWhatsApp : $settingsPhone);

        return [
            'name' => $this->contact_name,
            'phone' => $displayPhone,
            'tel' => PhoneNumber::toTel($displayPhone),
            'whatsapp' => PhoneNumber::toWhatsApp($whatsAppNumber),
            'source' => $source,
        ];
    }

    private function description(): string
    {
        if ($this->summary !== null && trim($this->summary) !== '') {
            return trim($this->summary);
        }

        /** @var HtmlSanitizer $sanitizer */
        $sanitizer = app(HtmlSanitizer::class);

        return Str::limit($sanitizer->toPlainText($this->introduction), 160);
    }
}
