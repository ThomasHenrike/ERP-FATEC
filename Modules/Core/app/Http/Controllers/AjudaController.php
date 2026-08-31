<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;

class AjudaController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $articles = collect(config('core.help', []))
            ->map(fn (array $article, string $slug) => [...$article, 'slug' => $slug])
            ->when($query !== '', function ($articles) use ($query) {
                $needle = Str::lower($query);

                return $articles->filter(function (array $article) use ($needle) {
                    if (Str::contains(Str::lower($article['title']), $needle)) {
                        return true;
                    }

                    foreach ($article['keywords'] as $keyword) {
                        if (Str::contains(Str::lower($keyword), $needle)) {
                            return true;
                        }
                    }

                    return false;
                });
            })
            ->sortBy('order')
            ->groupBy('category');

        /** @var Tenant $tenant */
        $tenant = app('currentTenant');
        $activeTicket = $tenant->tickets()->open()->latest()->first();

        return view('core::ajuda.index', [
            'articles' => $articles,
            'query' => $query,
            'activeTicket' => $activeTicket,
        ]);
    }

    public function show(string $slug): View
    {
        $article = config("core.help.{$slug}");

        abort_unless($article !== null, 404);

        $path = module_path('Core', "resources/help/{$slug}.md");

        abort_unless(File::exists($path), 404);

        $html = Str::markdown(File::get($path));

        return view('core::ajuda.show', [
            'article' => [...$article, 'slug' => $slug],
            'html' => $html,
        ]);
    }
}
