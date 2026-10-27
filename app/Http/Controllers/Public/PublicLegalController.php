<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Inertia\Inertia;
use Inertia\Response;

class PublicLegalController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    public function privacy(): Response
    {
        return Inertia::render('public/legal/privacy', [
            'content' => $this->settings->get('privacy_policy_content'),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('public/legal/terms', [
            'content' => $this->settings->get('terms_and_conditions_content'),
        ]);
    }
}
