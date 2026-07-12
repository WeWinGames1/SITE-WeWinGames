{{--
    Analytics tags that must sit at the top of <body>. Included by both the
    Inertia root shell (app.blade.php) and standalone raw pages.
--}}

{{-- Google Tag Manager (noscript) --}}
@production
    @if(config('google.tag_manager.container_id'))
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('google.tag_manager.container_id') }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif
@endproduction
