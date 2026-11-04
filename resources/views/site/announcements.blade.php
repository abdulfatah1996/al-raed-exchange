@extends('layouts.site', ['title' => __('web.nav.announcements')])

@section('content')
@include('site.partials.page-head', ['heading' => __('web.announcements.title'), 'lead' => __('web.announcements.lead')])
<div class="container-site mt-10">
    @if ($announcements->isEmpty())
        <p class="card p-8 text-center text-navy-900/60">{{ __('web.announcements_empty') }}</p>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($announcements as $a)
                @include('site.partials.announcement', ['a' => $a, 'full' => true])
            @endforeach
        </div>
        <div class="mt-8">{{ $announcements->links() }}</div>
    @endif
</div>
@endsection
