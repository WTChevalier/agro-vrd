<?php

namespace App\Concerns;

use App\Models\TraduccionContenido;
use Illuminate\Support\Facades\App;

/**
 * Provee accessors traducidos al locale activo automáticamente.
 *
 * Cuando un Model usa este trait y declara $translatable = ['name', 'description'],
 * cualquier acceso ($attr->name) en una request con locale != "es" sirve la
 * traducción de la tabla traducciones_contenido (con caching y fallback a ES).
 *
 * Sprint i18n turismo VRD - Día 2 + frontend (2026-04-29).
 */
trait HasTranslations
{
    /**
     * Override Eloquent getAttributeValue: si el campo está en $translatable
     * y el locale activo no es "es", devuelve la traducción.
     *
     * Esto hace que {{ $attr->name }} en Blade sirva el locale automáticamente
     * sin necesidad de cambiar Controllers ni Views.
     */
    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        // Solo intentar traducir si el campo está en la lista translatable
        if (! in_array($key, $this->translatable ?? [])) {
            return $value;
        }

        $locale = App::getLocale();
        if ($locale === "es") {
            return $value;
        }

        $traduccion = $this->fetchTraduccionFromCache($key, $locale);
        return $traduccion ?? $value;
    }

    /**
     * Cache miss-aware fetch para reducir DB roundtrips en frontend.
     */
    private function fetchTraduccionFromCache(string $field, string $locale): ?string
    {
        $cacheKey = "trans_{$this->getTable()}_{$this->getKey()}_{$locale}_{$field}";

        return cache()->remember($cacheKey, now()->addHours(6), function () use ($field, $locale) {
            return TraduccionContenido::where("item_type", $this->getTable())
                ->where("item_id", $this->getKey())
                ->where("locale", $locale)
                ->where("field", $field)
                ->value("content");
        });
    }

    /**
     * Forzar acceso explícito a una traducción de un locale específico.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? App::getLocale();

        if ($locale === "es") {
            return $this->getRawOriginal($field) ?? $this->attributes[$field] ?? null;
        }

        $traduccion = $this->fetchTraduccionFromCache($field, $locale);
        return $traduccion ?? $this->getRawOriginal($field) ?? $this->attributes[$field] ?? null;
    }

    /**
     * Settea una traducción para un campo específico.
     */
    public function setTranslation(string $field, string $locale, ?string $content, bool $autoTranslated = false): void
    {
        if ($locale === "es") {
            $this->{$field} = $content;
            $this->save();
            return;
        }

        TraduccionContenido::updateOrCreate(
            [
                "item_type" => $this->getTable(),
                "item_id" => $this->getKey(),
                "locale" => $locale,
                "field" => $field,
            ],
            [
                "content" => $content,
                "auto_translated" => $autoTranslated,
            ]
        );

        cache()->forget("trans_{$this->getTable()}_{$this->getKey()}_{$locale}_{$field}");
    }

    /**
     * Devuelve TODAS las traducciones del item como array nested.
     */
    public function getAllTranslations(): array
    {
        return TraduccionContenido::where("item_type", $this->getTable())
            ->where("item_id", $this->getKey())
            ->get()
            ->groupBy("locale")
            ->map(fn ($group) => $group->pluck("content", "field")->toArray())
            ->toArray();
    }

    public function translations()
    {
        return $this->hasMany(TraduccionContenido::class, "item_id")
            ->where("item_type", $this->getTable());
    }
}
