{{--
  Componente <x-marca.cta-block /> — Sprint 1067 (Fase 1 — Vive RD).

  CTA bloque final ("Únete a la red") — gran banner CTA al final del scroll
  para captar negocios al paraguas.
--}}
@php
    use App\Models\LandingConfig;

    $title = LandingConfig::get('cta.footer_title', '¿Tienes un negocio?');
    $subtitle = LandingConfig::get('cta.footer_subtitle', 'Únete gratis a la red Vive RD.');
    $buttonText = LandingConfig::get('cta.footer_button_text', 'Registrar mi negocio');
    $buttonUrl = LandingConfig::get('cta.footer_button_url', '/registro');
@endphp

<section class="cta-block py-16 sm:py-24"
         style="background: linear-gradient(135deg, var(--marca-primary) 0%, var(--marca-secondary) 100%);">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4 leading-tight">
            {{ $title }}
        </h2>
        <p class="text-lg sm:text-xl text-white/90 mb-8 max-w-2xl mx-auto">
            {{ $subtitle }}
        </p>
        <a href="{{ $buttonUrl }}"
           class="inline-flex items-center px-8 py-4 text-lg font-bold bg-white rounded-lg shadow-lg hover:shadow-2xl transition transform hover:-translate-y-0.5"
           style="color: var(--marca-primary);">
            {{ $buttonText }}
            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>
    </div>
</section>
