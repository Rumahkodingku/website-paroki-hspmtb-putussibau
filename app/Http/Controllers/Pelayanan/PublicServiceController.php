<?php

namespace App\Http\Controllers\Pelayanan;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Pelayanan\PublicServiceCardResource;
use App\Http\Resources\Public\Pelayanan\PublicServiceDetailResource;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicServiceController extends Controller
{
    public function index(): Response
    {
        $services = Service::active()
            ->with(['serviceCategory', 'thumbnailMedia.variants'])
            ->ordered()
            ->get();

        $grouped = $services->groupBy('service_category_id');

        $categories = ServiceCategory::query()
            ->whereIn('id', $services->pluck('service_category_id')->unique()->all())
            ->ordered()
            ->get();

        $groups = $categories
            ->map(fn (ServiceCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'services' => array_values(
                    PublicServiceCardResource::collection(
                        $grouped->get($category->id, new Collection)
                    )->resolve()
                ),
            ])
            ->values()
            ->all();

        return Inertia::render('public/pelayanan', [
            'groups' => $groups,
            'hasServices' => $services->isNotEmpty(),
        ]);
    }

    public function show(Service $service): Response
    {
        $service->load([
            'serviceCategory',
            'thumbnailMedia.variants',
            'requirements' => fn ($query) => $query->ordered(),
            'steps' => fn ($query) => $query->ordered(),
            'faqs' => fn ($query) => $query->ordered(),
        ]);

        $otherServices = Service::active()
            ->whereKeyNot($service->getKey())
            ->ordered()
            ->limit(6)
            ->get();

        return Inertia::render('public/pelayanan/show', [
            'service' => PublicServiceDetailResource::make($service)->resolve(),
            'otherServices' => array_values(
                PublicServiceCardResource::collection($otherServices)->resolve()
            ),
        ]);
    }
}
