<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function () {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="192x192" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon.ico">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4f46e5">

    {{-- Cloudflare Turnstile --}}
    @if(config('services.turnstile.enabled'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <script>
            window.turnstileConfig = {
                enabled: true,
                siteKey: '{{ config('services.turnstile.site_key') }}'
            };
        </script>
    @else
        <script>
            window.turnstileConfig = {
                enabled: false,
                siteKey: ''
            };
        </script>
    @endif

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

    {{-- OneSignal Push Notifications (non-admin pages only, HTTPS only) --}}
    @if(!request()->is('admin*') && request()->secure())
        <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
        <script>
            window.OneSignalDeferred = window.OneSignalDeferred || [];
            OneSignalDeferred.push(async function (OneSignal) {
                await OneSignal.init({
                    appId: "5d21734b-7157-455e-8c5e-7e287790fa5c",
                    notifyButton: {
                        enable: true,
                        position: 'bottom-right',
                        size: 'medium',
                        showCredit: false,
                        text: {
                            'tip.state.unsubscribed': 'Subscribe to notifications',
                            'tip.state.subscribed': "You're subscribed to notifications",
                            'tip.state.blocked': "You've blocked notifications",
                            'message.prenotify': 'Click to subscribe to notifications',
                            'message.action.subscribed': "Thanks for subscribing!",
                            'message.action.resubscribed': "You're subscribed to notifications",
                            'message.action.unsubscribed': "You won't receive notifications anymore",
                            'dialog.main.title': 'Manage Site Notifications',
                            'dialog.main.button.subscribe': 'SUBSCRIBE',
                            'dialog.main.button.unsubscribe': 'UNSUBSCRIBE',
                            'dialog.blocked.title': 'Unblock Notifications',
                            'dialog.blocked.message': 'Follow these instructions to allow notifications:'
                        }
                    },
                    allowLocalhostAsSecureOrigin: true,
                    promptOptions: {
                        slidedown: {
                            prompts: [
                                {
                                    type: "push",
                                    autoPrompt: false,
                                    text: {
                                        actionMessage: "We'd like to send you notifications for the latest betting picks and updates.",
                                        acceptButton: "Allow",
                                        cancelButton: "No Thanks"
                                    },
                                    delay: {
                                        pageViews: 1,
                                        timeDelay: 10
                                    }
                                }
                            ]
                        }
                    }
                });

                // Handle permission states gracefully - using new SDK methods
                OneSignal.Notifications.addEventListener('permissionPromptDisplay', function () {
                    console.log("OneSignal: Notification permission prompt displayed");
                });

                OneSignal.Notifications.addEventListener('permissionChange', function (isGranted) {
                    console.log('OneSignal: Notification permission changed to:', isGranted ? 'granted' : 'denied');

                    if (!isGranted) {
                        console.log('OneSignal: Notifications have been blocked. User needs to unblock in browser settings.');
                    }
                });

                // Check current permission status
                const permission = await OneSignal.Notifications.permissionNative;
                if (permission === 'denied') {
                    console.log('OneSignal: Notifications are currently blocked. The notification bell will show instructions to unblock.');
                }
            });
        </script>
    @endif

    {{-- Klaviyo Tracking (non-admin pages only) --}}
    @if(!request()->is('admin*'))
        <script async type='text/javascript'
            src='https://static.klaviyo.com/onsite/js/TZAz6i/klaviyo.js?company_id=TZAz6i'></script>
        <script type="text/javascript">
            //Initialize Klaviyo object on page load
            !function () { if (!window.klaviyo) { window._klOnsite = window._klOnsite || []; try { window.klaviyo = new Proxy({}, { get: function (n, i) { return "push" === i ? function () { var n; (n = window._klOnsite).push.apply(n, arguments) } : function () { for (var n = arguments.length, o = new Array(n), w = 0; w < n; w++)o[w] = arguments[w]; var t = "function" == typeof o[o.length - 1] ? o.pop() : void 0, e = new Promise((function (n) { window._klOnsite.push([i].concat(o, [function (i) { t && t(i), n(i) }])) })); return e } } }) } catch (n) { window.klaviyo = window.klaviyo || [], window.klaviyo.push = function () { var n; (n = window._klOnsite).push.apply(n, arguments) } } } }();
        </script>
    @endif

    {{-- jQuery and Select2 --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    @routes
    @vite(['resources/js/app.ts', 'resources/css/app.css'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    {{-- Google Tag Manager (noscript) --}}
    @production
        @if(config('google.tag_manager.container_id'))
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('google.tag_manager.container_id') }}"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
        @endif
    @endproduction

    @if(session()->has('impersonator_id'))
        <div class="bg-warning text-dark">
            <div class="container-fluid py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span class="fw-medium">
                            You are currently impersonating {{ Auth::user()->name }}
                        </span>
                    </div>
                    <a href="{{ route('admin.impersonate.stop') }}" class="btn btn-sm btn-dark">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Stop Impersonating
                    </a>
                </div>
            </div>
        </div>
    @endif
    @inertia

    {{-- MetaBet.io Live Odds Integration (non-admin pages only) --}}
    @if(!request()->is('admin*'))
        <script defer src="https://go.metabet.io/js/global.js?siteID=wewingames"></script>
        <script>
            (function() {
                // MetaBet initialization helper
                function initMetaBet() {
                    if (typeof window.mb_initializeProducts === 'function') {
                        console.log('[MetaBet] Initializing widgets');
                        window.mb_initializeProducts();
                    }
                }

                // Re-initialize MetaBet on Inertia page visits (for SPA navigation)
                document.addEventListener('inertia:success', function () {
                    // Multiple attempts to catch async component rendering
                    setTimeout(initMetaBet, 100);
                    setTimeout(initMetaBet, 500);
                    setTimeout(initMetaBet, 1000);
                });

                // Also watch for DOM changes that might add MetaBet widgets
                if (typeof MutationObserver !== 'undefined') {
                    var debounceTimer;
                    var observer = new MutationObserver(function(mutations) {
                        var hasMetabetElements = mutations.some(function(mutation) {
                            return Array.from(mutation.addedNodes).some(function(node) {
                                if (node.nodeType === 1) {
                                    return node.className && (
                                        node.className.includes('metabet-gametile') ||
                                        node.className.includes('metabet-sideoddstile')
                                    );
                                }
                                return false;
                            });
                        });

                        if (hasMetabetElements) {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(initMetaBet, 150);
                        }
                    });

                    // Start observing once DOM is ready
                    document.addEventListener('DOMContentLoaded', function() {
                        observer.observe(document.body, {
                            childList: true,
                            subtree: true
                        });
                    });
                }
            })();
        </script>
    @endif

    {{-- Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function (registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);

                    // Update service worker if needed
                    registration.update();
                }, function (err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>

</html>