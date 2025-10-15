<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Google Tag Manager --}}
        @production
            @if(config('google.tag_manager.container_id'))
                <!-- Google Tag Manager -->
                <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','{{ config('google.tag_manager.container_id') }}');</script>
                <!-- End Google Tag Manager -->
            @endif
        @endproduction

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
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
                    function gtag(){dataLayer.push(arguments);}
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
                OneSignalDeferred.push(async function(OneSignal) {
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
                    OneSignal.Notifications.addEventListener('permissionPromptDisplay', function() {
                        console.log("OneSignal: Notification permission prompt displayed");
                    });
                    
                    OneSignal.Notifications.addEventListener('permissionChange', function(isGranted) {
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
            <script src="https://go.metabet.io/js/global.js?siteID=wewingames" async></script>
            <script>
                // Trigger MetaBet to scan for game tiles after page load and after Inertia navigations
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
                        if (window.mb_initializeProducts) {
                            window.mb_initializeProducts();
                        }
                    }, 1000);
                });

                // Re-initialize on Inertia page visits
                document.addEventListener('inertia:success', function() {
                    setTimeout(function() {
                        if (window.mb_initializeProducts) {
                            window.mb_initializeProducts();
                        }
                    }, 500);
                });
            </script>
        @endif

        {{-- Service Worker Registration --}}
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                        
                        // Update service worker if needed
                        registration.update();
                    }, function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
                });
            }
        </script>
    </body>
</html>
