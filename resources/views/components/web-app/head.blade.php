@php
    $hotelName = setting('hotel_name', config('app.name'));
@endphp

<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0e151c">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
@if ($hotelName)
    <meta name="apple-mobile-web-app-title" content="{{ $hotelName }}">
@endif
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png">
