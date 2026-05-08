<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    @include('partials.seo-head')
    <link rel="stylesheet" href="/css/vive-rd.min.css?v=168139b5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --marca-primary: {{ $marca_color ?? '#ec4899' }};
            --marca-secondary: {{ $marca_color_secundario ?? '#831843' }};
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased">
    <x-marca.paraguas-bar />
    <x-marca.header />
    <main id="main-content" role="main">
        @yield('content')
    </main>
    <x-marca.footer />
</body>
</html>