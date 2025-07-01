<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Google Tag Manager --}}
        @if(config('google.tag_manager.container_id'))
            <!-- Google Tag Manager -->
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ config('google.tag_manager.container_id') }}');</script>
            <!-- End Google Tag Manager -->
        @endif

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
        <link rel="icon" type="image/png" sizes="96x96" href="/images/icons/icon-96x96.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/images/icons/icon-192x192.png">
        <link rel="apple-touch-icon" sizes="192x192" href="/images/icons/icon-192x192.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/images/icons/icon-512x512.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#4f46e5">
        <!-- Elfsight Google Reviews | Untitled Google Reviews -->
        @production
            <script defer src="https://go.metabet.io/js/global.js?siteID=wewingames"></script>
        @endproduction
        
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
        
        @routes
        @vite(['resources/js/app.ts', 'resources/css/app.css'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        {{-- Google Tag Manager (noscript) --}}
        @if(config('google.tag_manager.container_id'))
            <!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('google.tag_manager.container_id') }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->
        @endif
        
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
    </body>
</html>
