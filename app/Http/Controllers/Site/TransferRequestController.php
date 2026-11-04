<?php

namespace App\Http\Controllers\Site;

use App\Enums\RequestOperation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Models\DeliveryMethod;
use App\Models\TransferRequest;
use App\Support\SiteCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransferRequestController extends Controller
{
    public function create(Request $request): View
    {
        return view('site.request', [
            'groups' => SiteCatalog::groupedServices(),
            'countries' => Country::query()->active()->ordered()->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort')->get(),
            'deliveryMethods' => DeliveryMethod::query()->active()->ordered()->get(),
            'operations' => RequestOperation::cases(),
            'preselectService' => $request->integer('service') ?: null,
        ]);
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $data = collect($request->validated())->except(['consent', 'website'])->all();

        $transferRequest = new TransferRequest([...$data, 'locale' => app()->getLocale()]);
        $transferRequest->ip_hash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $transferRequest->save();

        return redirect()->route('site.request.done')->with('request_reference', $transferRequest->reference);
    }

    public function done(): View|RedirectResponse
    {
        $reference = session('request_reference');

        return $reference ? view('site.request-done', ['reference' => $reference]) : redirect()->route('site.request');
    }

    public function trackForm(): View
    {
        return view('site.track', ['result' => null, 'notFound' => false]);
    }

    public function track(Request $request): View
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $result = TransferRequest::track($data['reference'], $data['phone']);

        return view('site.track', ['result' => $result, 'notFound' => $result === null]);
    }
}
