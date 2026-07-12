{{--
    Shared analytics / advertising attribution injected into the <head>.
    Included by both the Inertia root shell (app.blade.php) and standalone raw
    HTML pages (raw-page.blade.php) so every public page inherits the same
    site-wide tracking. Page-specific scripts pasted into raw pages run in
    addition to these.
--}}

@if(config('services.reddit.pixel_id'))
    {{-- Reddit Pixel --}}
    <script>
        !function (w, d) { if (!w.rdt) { var p = w.rdt = function () { p.sendEvent ? p.sendEvent.apply(p, arguments) : p.callQueue.push(arguments) }; p.callQueue = []; var t = d.createElement("script"); t.src = "https://www.redditstatic.com/ads/pixel.js", t.async = !0; var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(t, s) } }(window, document);

        var redditInitOptions = {};
        @auth
            const userEmail = @json(Auth::user()->email);
            const userPhone = @json(Auth::user()->phone);

            redditInitOptions = {
                email: userEmail,
            };

            if (userPhone) {
                redditInitOptions.phoneNumber = userPhone;
            }
        @endauth

        rdt('init', '{{ config('services.reddit.pixel_id') }}', redditInitOptions);
        rdt('track', 'PageVisit');
    </script>
    <!-- End Reddit Pixel -->
@endif

{{-- Google Tag Manager --}}
@production
    @if(config('google.tag_manager.container_id'))
        <!-- Google Tag Manager -->
        <script>(function (w, d, s, l, i) {
                w[l] = w[l] || []; w[l].push({
                    'gtm.start':
                        new Date().getTime(), event: 'gtm.js'
                }); var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
                        'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', '{{ config('google.tag_manager.container_id') }}');</script>
        <!-- End Google Tag Manager -->
    @endif
@endproduction

{{-- Google Analytics --}}
@production
    @if(config('google.analytics.tag_id'))
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('google.analytics.tag_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '{{ config('google.analytics.tag_id') }}');
        </script>
    @endif
@endproduction

{{-- X (Twitter) Pixel --}}
@production
    @if(config('services.twitter.pixel_id'))
        <!-- Twitter conversion tracking base code -->
        <script>
            !function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
            },s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='https://static.ads-twitter.com/uwt.js',
            a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
            twq('config','{{ config('services.twitter.pixel_id') }}');
        </script>
        <!-- End Twitter conversion tracking base code -->
    @endif
@endproduction

{{-- Heatmap / session recording (Microsoft Clarity) --}}
@production
    @if(config('services.heatmap.id') && config('services.heatmap.provider') === 'clarity')
        <!-- Microsoft Clarity -->
        <script type="text/javascript">
            (function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","{{ config('services.heatmap.id') }}");
        </script>
        <!-- End Microsoft Clarity -->
    @endif
@endproduction
