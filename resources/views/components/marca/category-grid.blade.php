@php
use App\Models\LandingConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
$sectionTitle = LandingConfig::get("categorias.section_title", "Explora por categoría");
$sectionSubtitle = LandingConfig::get("categorias.section_subtitle", "");
$categorias = Schema::hasTable("categorias")
    ? DB::table("categorias")->orderBy("orden")->orderBy("nombre")->limit(12)->get()
    : collect();
@endphp

<section id="categorias" class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">{{ $sectionTitle }}</h2>
            @if(!empty($sectionSubtitle))<p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ $sectionSubtitle }}</p>@endif
        </div>
        @if($categorias->isEmpty())
            <div class="text-center py-12 text-gray-400"><p>Categorías próximamente</p></div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($categorias as $cat)
                @php
                    $icono = $cat->icono ?? "🏷";
                    $esFA = is_string($icono) && (str_starts_with($icono, "fa-") || str_starts_with($icono, "fas ") || str_starts_with($icono, "far ") || str_starts_with($icono, "fab "));
                @endphp
                <a href="/categoria/{{ $cat->slug ?? $cat->id }}" class="group block bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-200">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl mb-4 text-2xl" style="background: var(--marca-primary)15; color: var(--marca-primary);">
                        @if($esFA)
                            <i class="{{ str_starts_with($icono, "fa-") ? "fas " . $icono : $icono }}"></i>
                        @else
                            <span>{{ $icono }}</span>
                        @endif
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-gray-700">{{ $cat->nombre }}</h3>
                    @if(!empty($cat->descripcion))<p class="text-sm text-gray-600 line-clamp-3">{{ $cat->descripcion }}</p>@endif
                    <div class="mt-4 inline-flex items-center text-sm font-semibold" style="color: var(--marca-primary);">
                        Ver categoría
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                @endforeach
            </div>
        @endif
    </div>
</section>