<?php

namespace App\Http\Resources\Public\Komunitas;

use App\Models\Community;
use App\Services\SiteSettingsService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A community as the public detail page needs it.
 *
 * This resource is where XC-P1 is enforced, and it is the reason the rule cannot
 * live in React. `communities.show_contact` gates the community's own
 * contact_name and contact_phone. When it is false the `contact` key is
 * **omitted entirely**, not sent empty, so the value never reaches the browser
 * (AC-DETAIL-07).
 *
 * Optional sections the page hides when empty are sent as null rather than an
 * empty string, so React can tell "no vision" from "an empty vision".
 *
 * @mixin Community
 */
class PublicCommunityDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $socials = $this->socialLinks();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'vision' => $this->vision,
            'mission' => $this->mission,
            'meeting_schedule' => $this->meeting_schedule,
            'meeting_location' => $this->meeting_location,
            'eligibility' => $this->eligibility,
            'joining_info' => $this->joining_info,
            'thumbnail_url' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('medium')),
            'logo_url' => $this->whenLoaded('logoMedia', fn (): ?string => $this->logoMedia->url('medium')),
            'category' => $this->whenLoaded('communityCategory', fn (): array => [
                'id' => $this->communityCategory->getKey(),
                'name' => $this->communityCategory->name,
            ]),
            // The whole contact object is absent when consent is not given, so
            // there is nothing to leak even if a UI forgot to check.
            'contact' => $this->when(
                $this->show_contact,
                fn (): array => [
                    'name' => $this->contact_name,
                    'phone' => $this->contact_phone,
                ],
            ),
            /*
             * The community's own links, as label + url pairs so React renders
             * one list instead of five conditionals.
             *
             * The key is OMITTED entirely when every column is empty - the same
             * treatment `contact` gets, and for the same reason: a page that
             * receives five nulls still has to decide whether to draw a section,
             * and a caller cannot tell "no links" from "links withheld" if the
             * nulls are sent.
             *
             * The label travels with the url because the public page renders one
             * list, and reading the platform back out of a domain string in
             * React is exactly the guessing this resource exists to prevent.
             */
            'socials' => $this->when($socials !== [], fn (): array => $socials),

            // The parish secretariat, as the fallback CTA target. Null when the
            // parish has not filled either number in.
            'secretariat' => $this->secretariatContact(),
        ];
    }

    /**
     * The filled social links, in the order the admin form lists them.
     *
     * @return list<array{label: string, url: string}>
     */
    private function socialLinks(): array
    {
        $links = [
            'Facebook' => $this->facebook,
            'Instagram' => $this->instagram,
            'TikTok' => $this->tiktok,
            'YouTube' => $this->youtube,
            'Situs web' => $this->website,
        ];

        $filled = [];

        foreach ($links as $label => $url) {
            $url = $url === null ? '' : trim($url);

            if ($url !== '') {
                $filled[] = ['label' => $label, 'url' => $url];
            }
        }

        return $filled;
    }

    /**
     * The global contact, read from site settings (D-55 point 8).
     *
     * @return array<string, mixed>|null
     */
    private function secretariatContact(): ?array
    {
        /** @var SiteSettingsService $settings */
        $settings = app(SiteSettingsService::class);

        $phone = trim((string) $settings->get('contact_phone'));
        $whatsapp = trim((string) $settings->get('contact_whatsapp'));

        if ($phone === '' && $whatsapp === '') {
            return null;
        }

        $telTarget = $phone !== '' ? $phone : $whatsapp;
        $whatsAppTarget = $whatsapp !== '' ? $whatsapp : $phone;

        return [
            'phone' => $phone !== '' ? $phone : null,
            'tel' => PhoneNumber::toTel($telTarget),
            'whatsapp' => PhoneNumber::toWhatsApp($whatsAppTarget),
        ];
    }
}
