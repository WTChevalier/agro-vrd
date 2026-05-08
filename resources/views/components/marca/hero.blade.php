{{--
  Componente <x-marca.hero /> — Sprint 1067 (Fase 1 — Vive RD).

  Hero principal de la landing. CMS-driven 100%.
  Lee de landings_config: hero.title, hero.subtitle, hero.cta_*, hero.image_url.
--}}
@php
    use App\Models\LandingConfig;

    $titulo = LandingConfig::get('hero.title', 'Bienvenido');
    $subtitulo = LandingConfig::get('hero.subtitle', '');
    $ctaPrimaryText = LandingConfig::get('hero.cta_primary_text', 'Explorar');
    $ctaPrimaryUrl = LandingConfig::get('hero.cta_primary_url', '#categorias');
    $ctaSecondaryText = LandingConfig::get('hero.cta_secondary_text', '');
    $ctaSecondaryUrl = LandingConfig::get('hero.cta_secondary_url', '#');
    $heroImage = LandingConfig::get('hero.image_url', null);
@endphp

<section class="hero relative overflow-hidden">
    {{-- Background gradient suave usando paleta marca --}}
    <div class="absolute inset-0 -z-10"
         style="background: linear-gradient(135deg, var(--marca-primary)08 0%, var(--marca-secondary)05 100%);"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            {{-- Columna izquierda: copy + CTAs --}}
            <div class="text-center lg:text-left">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                    {{ $titulo }}
                </h1>

                @if (!empty($subtitulo))
                    <p class="text-lg sm:text-xl text-gray-600 mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                        {{ $subtitulo }}
                    </p>
                @endif

                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @if (!empty($ctaPrimaryText))
                        <a href="{{ $ctaPrimaryUrl }}"
                           class="inline-flex items-center justify-center px-8 py-3 text-base font-semibold text-white rounded-lg transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                           style="background: var(--marca-primary);">
                            {{ $ctaPrimaryText }}
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    @endif

                    @if (!empty($ctaSecondaryText))
                        <a href="{{ $ctaSecondaryUrl }}"
                           class="inline-flex items-center justify-center px-8 py-3 text-base font-semibold rounded-lg border-2 transition hover:bg-white"
                           style="color: var(--marca-primary); border-color: var(--marca-primary);">
                            {{ $ctaSecondaryText }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Columna derecha: imagen hero (lazy-loaded) --}}
            @if ($heroImage)
                <div class="relative">
                    <img src="{{ asset($heroImage) }}"
                         alt="{{ $titulo }}"
                         loading="eager"
                         decoding="async"
                         width="600"
                         height="600"
                         class="w-full h-auto rounded-2xl shadow-2xl"
                         style="aspect-ratio: 1/1; object-fit: cover;">
                </div>
            @endif
        </div>
    </div>
</section>
