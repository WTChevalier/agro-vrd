@php
$_host = request()->getHost();
$marcaSlug = match(true) {
    str_starts_with($_host, 'estilo.')  => 'estilo',
    str_starts_with($_host, 'servi.')   => 'servi',
    str_starts_with($_host, 'inmo.')    => 'inmo',
    str_starts_with($_host, 'educ.')    => 'educ',
    str_starts_with($_host, 'agro.')    => 'agro',
    str_starts_with($_host, 'turismo.') => 'visit',
    str_starts_with($_host, 'visit.')   => 'visit',
    default => $marca_slug ?? 'estilo',
};
$marcaNombre = ["estilo"=>"EstiloRD","servi"=>"ServiRD","inmo"=>"InmoRD","educ"=>"EducRD","agro"=>"AgroRD","visit"=>"Visit RD"][$marcaSlug] ?? "Vive RD";
$cta_secundario_text = \App\Models\LandingConfig::get("hero.cta_secondary_text", "Soy un negocio · Únete");
$cta_secundario_url = \App\Models\LandingConfig::get("hero.cta_secondary_url", "/registro");
$emoji = ["estilo"=>"💄","servi"=>"🛠","inmo"=>"🏠","educ"=>"🎓","agro"=>"🌾","visit"=>"🏖"][$marcaSlug] ?? "⭐";
@endphp

<header class="bg-white border-b border-gray-200 sticky top-0 z-40 backdrop-blur-md bg-white/95">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2 text-xl font-bold" style="color: var(--marca-primary);">
                <span class="text-2xl">{{ $emoji }}</span>
                <span>{{ $marcaNombre }}</span>
            </a>
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-700">
                <a href="#categorias" class="hover:text-gray-900 transition">Categorías</a>
                <a href="#como-funciona" class="hover:text-gray-900 transition">Cómo funciona</a>
                <a href="/contacto" class="hover:text-gray-900 transition">Contacto</a>
            </nav>
            <a href="{{ $cta_secundario_url }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-lg shadow-sm hover:shadow-md transition" style="background: var(--marca-primary);">
                {{ $cta_secundario_text }}
            </a>
        </div>
    </div>
</header>