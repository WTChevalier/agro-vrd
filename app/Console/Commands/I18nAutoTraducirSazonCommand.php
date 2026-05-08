<?php

namespace App\Console\Commands;

use App\Models\TraduccionContenido;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Comando i18n:auto-traducir-sazon
 *
 * Genera traducciones del contenido editorial de SazónRD a 9 idiomas
 * (en, fr, it, de, pt, ru, ja, ko, zh) con fuente española.
 *
 * Cobertura: platos, restaurantes, blog_posts.
 *
 * Sprint i18n cross-marca - SazónRD (2026-04-29).
 */
class I18nAutoTraducirSazonCommand extends Command
{
    protected $signature = "i18n:auto-traducir-sazon
        {--locale= : Solo traducir a este locale (ej: en)}
        {--tabla= : Solo procesar esta tabla}
        {--limit=10 : Max records por tabla}
        {--dry-run : Solo mostrar lo que haría}
        {--force : Re-traducir aunque ya exista}";

    protected $description = "Sprint i18n SazónRD - Traduce contenido (platos, restaurantes, blog) a 9 idiomas";

    private array $tablasContenido = [
        "platos" => ["nombre", "descripcion"],
        "restaurantes" => ["nombre", "descripcion", "direccion"],
        "blog_posts" => ["title", "excerpt", "content"],
    ];

    public function handle(): int
    {
        $locales = $this->option("locale")
            ? [$this->option("locale")]
            : TraduccionContenido::localesNoBase();

        $tablas = $this->option("tabla")
            ? [$this->option("tabla") => ($this->tablasContenido[$this->option("tabla")] ?? [])]
            : $this->tablasContenido;

        $limit = (int) $this->option("limit");
        $dryRun = $this->option("dry-run");
        $force = $this->option("force");

        $backend = env("DEEPL_API_KEY") ? "deepl" : "google-free";
        $this->info("Backend: {$backend}");
        $this->info("Locales: " . implode(",", $locales));
        $this->info("Tablas: " . count($tablas) . " | Limit: {$limit}");
        $this->newLine();

        $totalT = 0;
        $totalS = 0;
        $totalE = 0;

        foreach ($tablas as $tabla => $campos) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($tabla)) {
                $this->warn("  ⚠ Tabla {$tabla} no existe");
                continue;
            }

            $this->info("📋 {$tabla}");
            $records = DB::table($tabla)->limit($limit)->get();

            foreach ($records as $record) {
                foreach ($campos as $campo) {
                    if (!isset($record->{$campo}) || empty($record->{$campo})) continue;

                    foreach ($locales as $locale) {
                        if (!$force) {
                            $exists = TraduccionContenido::where([
                                "item_type" => $tabla,
                                "item_id" => $record->id,
                                "locale" => $locale,
                                "field" => $campo,
                            ])->exists();
                            if ($exists) { $totalS++; continue; }
                        }

                        $textoOriginal = $record->{$campo};
                        if ($dryRun) {
                            $this->line("    [DRY] {$tabla}#{$record->id}.{$campo} → {$locale}");
                            continue;
                        }

                        try {
                            $traducido = $this->traducir($textoOriginal, $locale, $backend);
                            if ($traducido) {
                                TraduccionContenido::create([
                                    "item_type" => $tabla,
                                    "item_id" => $record->id,
                                    "locale" => $locale,
                                    "field" => $campo,
                                    "content" => $traducido,
                                    "auto_translated" => true,
                                ]);
                                $totalT++;
                                $this->line("    ✓ {$tabla}#{$record->id}.{$campo} → {$locale}");
                            }
                        } catch (\Exception $e) {
                            $totalE++;
                            $this->error("    ✗ " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Traducidos: {$totalT} | Skipped: {$totalS} | Errores: {$totalE}");
        return self::SUCCESS;
    }

    private function traducir(string $texto, string $locale, string $backend): ?string
    {
        if ($backend === "deepl") return $this->traducirDeepL($texto, $locale);
        return $this->traducirGoogleFree($texto, $locale);
    }

    private function traducirDeepL(string $texto, string $locale): ?string
    {
        $key = env("DEEPL_API_KEY");
        $endpoint = str_contains($key, ":fx") ? "https://api-free.deepl.com/v2/translate" : "https://api.deepl.com/v2/translate";
        $r = Http::asForm()->withHeaders(["Authorization" => "DeepL-Auth-Key {$key}"])
            ->post($endpoint, [
                "text" => $texto, "source_lang" => "ES",
                "target_lang" => strtoupper($locale === "zh" ? "ZH" : ($locale === "pt" ? "PT-BR" : $locale)),
            ]);
        if (!$r->successful()) throw new \Exception("DeepL HTTP " . $r->status());
        return $r->json("translations.0.text");
    }

    private function traducirGoogleFree(string $texto, string $locale): ?string
    {
        $r = Http::timeout(10)->get("https://translate.googleapis.com/translate_a/single", [
            "client" => "gtx", "sl" => "es",
            "tl" => $locale === "zh" ? "zh-CN" : ($locale === "pt" ? "pt" : $locale),
            "dt" => "t", "q" => $texto,
        ]);
        if (!$r->successful()) throw new \Exception("Google HTTP " . $r->status());
        $data = $r->json();
        if (!is_array($data) || empty($data[0])) return null;
        return collect($data[0])->pluck(0)->implode(" ");
    }
}
