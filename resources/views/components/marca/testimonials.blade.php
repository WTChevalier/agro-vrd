{{--
  Componente <x-marca.testimonials /> — Sprint 1067 (Fase 1 — Vive RD).

  Carrousel de testimonios con Alpine.js + auto-rotate cada 6s.
  Pulled de tabla landing_blocks WHERE tipo='testimonial' AND activo=1.
--}}
@php
    use App\Models\LandingBlock;

    $testimonios = LandingBlock::activos('testimonial');
@endphp

@if ($testimonios->isNotEmpty())
<section class="testimonials py-16 sm:py-20 bg-gradient-to-b from-white to-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl sm:text-4xl font-bold text-center text-gray-900 mb-12">
            {{ __('Lo que dicen') }}
        </h2>

        <div x-data="{
                current: 0,
                total: {{ $testimonios->count() }},
                next() { this.current = (this.current + 1) % this.total },
                prev() { this.current = (this.current - 1 + this.total) % this.total
                },
                init() {
                    setInterval(() => this.next(), 6000);
                }
             }"
             class="relative">
            @foreach ($testimonios as $i => $t)
                @php
                    $contenido = $t->contenido;
                    $texto = $contenido['texto'] ?? '';
                    $nombre = $contenido['nombre'] ?? 'Anónimo';
                    $ciudad = $contenido['ciudad'] ?? '';
                    $rating = (int) ($contenido['rating'] ?? 5);
                    $avatar = $contenido['avatar'] ?? null;
                @endphp
                <div x-show="current === {{ $i }}" x-transition.duration.500ms
                     class="bg-white rounded-2xl shadow-xl p-8 sm:p-12 text-center">
                    <div class="text-yellow-400 mb-4">
                        @for ($s = 0; $s < $rating; $s++)
                            <i class="fas fa-star"></i>
                        @endfor
                    </div>
                    <blockquote class="text-xl sm:text-2xl text-gray-800 leading-relaxed mb-8 italic">
                        "{{ $texto }}"
                    </blockquote>
                    <div class="flex items-center justify-center gap-3">
                        @if ($avatar)
                            <img src="{{ $avatar }}" alt="{{ $nombre }}" class="w-12 h-12 rounded-full object-cover" loading="lazy">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-lg font-bold">
                                {{ mb_substr($nombre, 0, 1) }}
                            </div>
                        @endif
                        <div class="text-left">
                            <div class="font-bold text-gray-900">{{ $nombre }}</div>
                            @if ($ciudad)
                                <div class="text-sm text-gray-600">{{ $ciudad }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Navigation dots --}}
            <div class="flex justify-center gap-2 mt-6">
                @foreach ($testimonios as $i => $t)
                    <button @click="current = {{ $i }}"
                            :class="current === {{ $i }} ? 'w-8' : 'w-2'"
                            class="h-2 rounded-full bg-gray-300 transition-all duration-200 hover:bg-gray-400"
                            aria-label="Testimonio {{ $i + 1 }}"></button>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
