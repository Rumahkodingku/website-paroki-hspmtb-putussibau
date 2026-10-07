<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Site settings, at /admin/pengaturan.
 *
 * PRD 5.2 puts "Pengaturan situs dan kontak" on this path. It is a different
 * thing from /admin/akun, which holds per-account preferences: the parish name
 * belongs to the site, the colour scheme belongs to the person looking at it.
 *
 * The props are built from the configuration rather than hard-coded, so adding
 * a key to config/site-settings.php makes it appear in the form.
 */
class SettingsController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    /**
     * Show the site settings page.
     *
     * Gate here rather than in the form request: this route has no request to
     * authorise, so ARCHITECTURE.md Part C section 8 puts the check in the
     * controller.
     */
    public function edit(): Response
    {
        Gate::authorize('settings.view');

        return Inertia::render('admin/pengaturan', [
            'groups' => $this->groupsWithValues(),
            'meta' => $this->formMeta(),
        ]);
    }

    /**
     * Save the site settings.
     *
     * The request authorizes with settings.update and also validates the payload,
     * so nothing is written until both pass.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->setMany($request->settingsPayload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Pengaturan situs berhasil disimpan.'),
        ]);

        return to_route('settings.edit');
    }

    /**
     * Presentation hints for the form, so the React side does not have to carry a
     * second copy of which field is a textarea, a URL or a bounded number.
     *
     * @return array<string, mixed>
     */
    private function formMeta(): array
    {
        return [
            'labels' => (array) __('site-settings.labels'),
            'multiline' => (array) __('site-settings.multiline'),
            'urls' => (array) __('site-settings.urls'),
            'groupLabels' => (array) __('site-settings.groups'),
            'numericRanges' => [
                'home_news_limit' => [3, 6],
                'home_events_limit' => [3, 5],
                'home_gallery_limit' => [1, 12],
            ],
        ];
    }

    /**
     * Every configured group with its current values filled in.
     *
     * @return array<string, array<string, mixed>>
     */
    private function groupsWithValues(): array
    {
        $result = [];

        foreach (array_keys((array) config('site-settings.groups', [])) as $group) {
            $result[$group] = $this->settings->getGroup((string) $group);
        }

        return $result;
    }
}
