<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    {{-- Site-wide analytics & advertising attribution --}}
    {!! $trackingHead !!}
</head>

<body>
    {{-- Analytics tags for top of <body> --}}
    {!! $trackingBody !!}

    {{-- Admin-authored raw HTML (trusted, admin-only) --}}
    {!! $body !!}
</body>

</html>
