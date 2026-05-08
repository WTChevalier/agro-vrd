<?php

namespace App\Observers;

use App\Models\TraduccionContenido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sprint i18n turismo VRD - Observer auto-translate (2026-04-30)
 *
 * Cuando un Model que use HasTranslations se crea o actualiza,
 * dispara traducción automática a los 9 locales restantes.
 *
 * Uso: en cada Model que tenga HasTranslations, agregar al boot:
 *   protected static function booted() {
 *       static::observe(TranslatableObserver::class);
 *   }
 *
 * O registrar globalmente en AppServiceProvider:
 *   foreach ([Attraction::class, Beach::class, ...] as $model) {
 *       $model::observe(TranslatableObserver::class);
 *   }
 */
class TranslatableObserver
{
    private const LOCALES_OBJETIVO = ["en", "fr", "it", "de", "pt", "ru", "ja", "ko", "zh"];

    public function created(Model $model): void
    {
        $this->traducirCampos($model);
    }

    public function updated(Model $model): void
    {
        // Solo re-traducir si campos translatable cambiaron
        $translatable = $model->translatable ?? [];
        $cambioTranslatable = false;
        foreach ($translatable as $field) {
            if ($model->wasChanged($field)) {
                $cambioTranslatable = true;
                break;
            }
        }
        if ($cambioTranslatable) {
            $this->traducirCampos($model, soloCambiados: true);
        }
    }

    public function deleted(Model $model): void
    {
        // Limpiar traducciones huérfanas
        TraduccionContenido::where("item_type", $model->getTable())
            ->where("item_id", $model->getKey())
            ->delete();
    }

    private function traducirCampos(Model $model, bool $soloCambiados = false): void
    {
        $translatable = $model->translatable ?? [];
        if (empty($translatable)) return;

        foreach ($translatable as $field) {
            if ($soloCambiados && !$model->wasChanged($field)) continue;

            $valor = $model->getRawOriginal($field) ?? $model->{$field};
            if (empty($valor)) continue;

            foreach (self::LOCALES_OBJETIVO as $locale) {
                try {
                    // Sync mode (sin queue): traduce inmediatamente
                    // Si volume crece, mover a queue jobs
                    $traducido = $this->traducirGoogleFree($valor, $locale);
                    if ($traducido) {
                        TraduccionContenido::updateOrCreate(
                            [
                                "item_type" => $model->getTable(),
                                "item_id" => $model->getKey(),
                                "locale" => $locale,
                                "field" => $field,
                            ],
                            [
                                "content" => $traducido,
                                "auto_translated" => true,
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning("Auto-translate failed", [
                        "model" => $model->getTable(),
                        "id" => $model->getKey(),
                        "field" => $field,
                        "locale" => $locale,
                        "error" => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function traducirGoogleFree(string $texto, string $locale): ?string
    {
        $r = Http::timeout(10)->get("https://translate.googleapis.com/translate_a/single", [
            "client" => "gtx",
            "sl" => "es",
            "tl" => $locale === "zh" ? "zh-CN" : ($locale === "pt" ? "pt" : $locale),
            "dt" => "t",
            "q" => $texto,
        ]);

        if (!$r->successful()) return null;
        $data = $r->json();
        if (!is_array($data) || empty($data[0])) return null;
        return collect($data[0])->pluck(0)->implode(" ");
    }
}
