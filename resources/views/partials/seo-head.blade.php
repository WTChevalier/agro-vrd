@php
use App\Models\LandingConfig;
$metaTitle = LandingConfig::get('seo.meta_title', config('app.name'));
$metaDescription = LandingConfig::get('seo.meta_description', '');
$canonicalUrl = url()->current();
@endphp

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="alternate" hreflang="es" href="https://estilo.vrd.do/">
<link rel="alternate" hreflang="en" href="https://estilo.vrd.do/en">
<link rel="alternate" hreflang="fr" href="https://estilo.vrd.do/fr">
<link rel="alternate" hreflang="de" href="https://estilo.vrd.do/de">
<link rel="alternate" hreflang="it" href="https://estilo.vrd.do/it">
<link rel="alternate" hreflang="pt" href="https://estilo.vrd.do/pt">
<link rel="alternate" hreflang="ja" href="https://estilo.vrd.do/ja">
<link rel="alternate" hreflang="zh" href="https://estilo.vrd.do/zh">
<link rel="alternate" hreflang="x-default" href="https://estilo.vrd.do/">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:site_name" content="Vive RD">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
