@php
$aboutTitle = \App\Models\LandingConfig::get("footer.about_title", "Sobre Vive RD");
$aboutText = \App\Models\LandingConfig::get("footer.about_text", "Vive RD es el paraguas digital de la República Dominicana.");
$copyright = \App\Models\LandingConfig::get("footer.copyright", "© Gurztac Productions Inc · Todos los derechos reservados");
$ano = date("Y");
@endphp

<footer class="bg-gray-900 text-gray-300 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-white font-bold text-lg mb-4">{{ $aboutTitle }}</h3>
                <p class="text-sm leading-relaxed">{{ $aboutText }}</p>
            </div>
            <div>
                <h3 class="text-white font-bold text-lg mb-4">Verticales</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="https://visitrepublicadominicana.com" class="hover:text-white">🏖 Visit RD</a></li>
                    <li><a href="https://estilo.vrd.do" class="hover:text-white">💄 EstiloRD</a></li>
                    <li><a href="https://servi.vrd.do" class="hover:text-white">🛠 ServiRD</a></li>
                    <li><a href="https://inmo.vrd.do" class="hover:text-white">🏠 InmoRD</a></li>
                    <li><a href="https://educ.vrd.do" class="hover:text-white">🎓 EducRD</a></li>
                    <li><a href="https://agro.vrd.do" class="hover:text-white">🌾 AgroRD</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-bold text-lg mb-4">Vive RD</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="https://vrd.do" class="hover:text-white">Paraguas Vive RD</a></li>
                    <li><a href="/registro" class="hover:text-white">Registrar mi negocio</a></li>
                    <li><a href="/contacto" class="hover:text-white">Contacto</a></li>
                    <li><a href="/privacidad" class="hover:text-white">Privacidad</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-bold text-lg mb-4">Conéctate</h3>
                <p class="text-xs text-gray-500">Made with ❤️ in 🇩🇴 República Dominicana</p>
            </div>
        </div>
        <div class="mt-12 pt-8 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500">
            <div>{{ str_replace("©", "© " . $ano, $copyright) }}</div>
            <div>🌴 Vive RD · Marca País Digital</div>
        </div>
    </div>
</footer>