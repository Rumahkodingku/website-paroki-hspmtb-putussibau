<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seo\UpdatePageSeoRequest;
use App\Models\PageSeo;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The static-page SEO editor at /admin/pengaturan/seo.
 *
 * The page keys come from config/seo.php, never from the request. The form
 * posts every page; a page whose fields are all empty deletes its row so the
 * table stays a list of pages that actually have an override.
 */
class PageSeoController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit(): Response
    {
        Gate::authorize('seo.view');

        return Inertia::render('admin/seo', [
            'pages' => $this->pages(),
        ]);
    }

    public function update(UpdatePageSeoRequest $request): RedirectResponse
    {
        $payload = $request->pagesPayload();

        DB::transaction(function () use ($payload): void {
            foreach ($payload as $key => $values) {
                $row = PageSeo::query()->where('page_key', $key)->first();
                $before = $row === null ? [] : $this->snapshot($row);

                if ($this->isEmpty($values)) {
                    if ($row !== null) {
                        $this->audit->deleted(
                            'page_seo',
                            $row,
                            $before,
                            $this->label($key),
                        );
                        $row->delete();
                    }

                    continue;
                }

                $page = PageSeo::query()->updateOrCreate(
                    ['page_key' => $key],
                    $values,
                );

                $after = $this->snapshot($page);

                if ($before === $after) {
                    continue;
                }

                $this->audit->recordModel(
                    'page_seo',
                    'updated',
                    'success',
                    $page,
                    $before,
                    $after,
                    label: $this->label($key),
                );
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('SEO halaman berhasil disimpan.'),
        ]);

        return to_route('seo.edit');
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     url: string,
     *     meta_title: string|null,
     *     meta_description: string|null,
     *     og_media_id: string|null,
     *     og_image_url: string|null
     * }>
     */
    private function pages(): array
    {
        /** @var array<string, array{route: string, label: string, title: string|null, description: string|null}> $pages */
        $pages = (array) config('seo.static_pages', []);

        $rows = PageSeo::query()
            ->with(['media.variants'])
            ->whereIn('page_key', array_keys($pages))
            ->get()
            ->keyBy('page_key');

        $result = [];

        foreach ($pages as $key => $page) {
            $row = $rows->get($key);

            $result[] = [
                'key' => $key,
                'label' => $page['label'],
                'url' => route($page['route']),
                'meta_title' => $row?->meta_title,
                'meta_description' => $row?->meta_description,
                'og_media_id' => $row?->og_media_id === null ? null : (string) $row->og_media_id,
                'og_image_url' => $row?->ogImageUrl(),
            ];
        }

        return $result;
    }

    /**
     * @param  array{meta_title: string|null, meta_description: string|null, og_media_id: string|null}  $values
     */
    private function isEmpty(array $values): bool
    {
        return $values['meta_title'] === null
            && $values['meta_description'] === null
            && $values['og_media_id'] === null;
    }

    /**
     * @return array{page_key: string, meta_title: string|null, meta_description: string|null, og_media_id: int|null}
     */
    private function snapshot(PageSeo $page): array
    {
        return [
            'page_key' => $page->page_key,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'og_media_id' => $page->og_media_id,
        ];
    }

    private function label(string $key): string
    {
        /** @var array<string, array{route: string, label: string, title: string|null, description: string|null}> $pages */
        $pages = (array) config('seo.static_pages', []);

        return $pages[$key]['label'] ?? $key;
    }
}
