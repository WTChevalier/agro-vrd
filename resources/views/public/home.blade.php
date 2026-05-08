@extends('layouts.public', [
    'marca_slug' => 'agro',
    'marca_color' => '#16a34a',
    'marca_color_secundario' => '#14532d',
])

@section('content')
    <x-marca.hero />
    <x-marca.stats-bar />
    <x-marca.category-grid />

    @php
        $features = \App\Models\LandingBlock::activos('feature');
    @endphp
    @if ($features->isNotEmpty())
        <section id="como-funciona" class="py-16 sm:py-20 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl font-bold text-center text-gray-900 mb-12">Cómo funciona</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($features as $f)
                        @php $c = $f->contenido; @endphp
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4"
                                 style="background: var(--marca-primary)15; color: var(--marca-primary);">
                                <i class="fas {{ $c['icono'] ?? 'fa-check' }} text-2xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $c['titulo'] ?? '' }}</h3>
                            <p class="text-gray-600">{{ $c['descripcion'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-marca.testimonials />
    <x-marca.cta-block />
@endsection
