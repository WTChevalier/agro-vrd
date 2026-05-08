{{--
  Componente <x-marca.paraguas-bar /> — Sprint 1067 (Fase 1 — Vive RD).

  Banda superior cross-marca con los 3 colores de la bandera dominicana
  (rojo / blanco / azul). Identifica que este vertical es parte del paraguas Vive RD.

  Compartido idéntico en TODOS los verticales — modificar aquí afecta a los 6 sites.
--}}
@php
    $paraguasLabel = \App\Models\LandingConfig::get(
        'paraguas.label',
        'Eres parte de Vive RD · El paraguas digital de Marca País'
    );
@endphp

<div class="paraguas-bar w-full text-white text-xs sm:text-sm py-2 px-4"
     style="background: linear-gradient(90deg, #002D62 0%, #002D62 33.3%, #FFFFFF 33.3%, #FFFFFF 66.6%, #CE1126 66.6%, #CE1126 100%);">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="inline-block w-5 h-5 bg-white rounded-full p-1 flex items-center justify-center text-xs font-bold" style="color: #002D62;">
                🌴
            </span>
            <a href="https://vrd.do{{ app()->getLocale() === 'es' ? '' : '/'.app()->getLocale() }}"
               class="font-medium hover:underline"
               style="color: #002D62; text-shadow: 0 0 2px rgba(255,255,255,0.5);">
                {{ $paraguasLabel }}
            </a>
        </div>

        {{-- Mini-nav cross-marcas hermanas --}}
        <nav class="hidden sm:flex items-center gap-3 text-xs">
            <a href="https://visitrepublicadominicana.com" class="hover:underline" style="color: #002D62;">🏖 Visit RD</a>
            <a href="https://estilo.vrd.do" class="hover:underline" style="color: #002D62;">💄 EstiloRD</a>
            <a href="https://servi.vrd.do" class="hover:underline" style="color: #002D62;">🛠 ServiRD</a>
            <a href="https://inmo.vrd.do" class="hover:underline" style="color: #002D62;">🏠 InmoRD</a>
            <a href="https://educ.vrd.do" class="hover:underline" style="color: #002D62;">🎓 EducRD</a>
            <a href="https://agro.vrd.do" class="hover:underline" style="color: #002D62;">🌾 AgroRD</a>
        </nav>
    </div>
</div>
