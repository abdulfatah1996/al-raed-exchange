<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\DeliveryMethod;
use App\Support\SiteCatalog;
use Illuminate\Contracts\View\View;

class SiteController extends Controller
{
    private const PAGES = ['instructions', 'privacy', 'terms', 'legal'];

    public function home(): View
    {
        return view('site.home', [
            'groups' => SiteCatalog::groupedServices(),
            'deliveryMethods' => DeliveryMethod::query()->active()->ordered()->get(),
            'announcements' => Announcement::query()->visible()->limit(3)->get(),
        ]);
    }

    public function services(): View
    {
        return view('site.services', ['groups' => SiteCatalog::groupedServices()]);
    }

    public function announcements(): View
    {
        return view('site.announcements', ['announcements' => Announcement::query()->visible()->paginate(12)]);
    }

    public function contact(): View
    {
        return view('site.contact');
    }

    public function page(string $slug): View
    {
        abort_unless(in_array($slug, self::PAGES, true), 404);

        return view('site.page', ['slug' => $slug, 'pages' => self::PAGES]);
    }
}
