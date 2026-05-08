@php
    use App\Models\LandingConfig;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    $statsToShow = LandingConfig::get('stats.show', ['categorias', 'negocios', 'ciudades']);
    if (!is_array($statsToShow)) {
        $statsToShow = ['categorias', 'negocios', 'ciudades'];
    }

    $safeCount = function (string $table, callable $query) {
        try {
            if (!Schema::hasTable($table)) return 0;
            return $query();
        } catch (\Throwable $e) {
            return 0;
        }
    };

    $counts = [
        'categorias' => $safeCount('categorias', fn() => DB::table('categorias')->count()),
        'negocios' => $safeCount('negocios', fn() => DB::table('negocios')->count()),
        'ciudades' => $safeCount('negocios', fn() => Schema::hasColumn('negocios', 'ciudad')
            ? DB::table('negocios')->whereNotNull('ciudad')->distinct('ciudad')->count('ciudad')
            : 0),
        'resenas' => $safeCount('resenas', fn() => DB::table('resenas')->count()),
    ];

    $labels = [
        'categorias' => LandingConfig::get('stats.categorias_label', 'Categorías activas'),
        'negocios' => LandingConfig::get('stats.negocios_label', 'Negocios registrados'),
        'ciudades' => LandingConfig::get('stats.ciudades_label', 'Ciudades cubiertas'),
        'resenas' => LandingConfig::get('stats.resenas_label', 'Reseñas verificadas'),
    ];

    $iconos = [
        'categorias' => 'fa-th-large',
        'negocios' => 'fa-store',
        'ciudades' => 'fa-map-marker-alt',
        'resenas' => 'fa-star',
    ];
@endphp

<section class="stats-bar py-12 sm:py-16 bg-gray-50 border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-{{ count($statsToShow) }} gap-6 sm:gap-8">
            @foreach ($statsToShow as $stat)
                @php
                    $count = $counts[$stat] ?? 0;
                    $label = $labels[$stat] ?? $stat;
                    $icon = $iconos[$stat] ?? 'fa-chart-bar';
                @endphp
                <div class="stat text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-3"
                         style="background: var(--marca-primary)15; color: var(--marca-primary);">
                        <i class="fas {{ $icon }} text-2xl"></i>
                    </div>
                    <div class="text-3xl sm:text-4xl font-bold text-gray-900 mb-1">
                        {{ number_format($count) }}{{ $count > 0 ? '+' : '' }}
                    </div>
                    <div class="text-sm text-gray-600">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
