<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla central de traducciones del contenido editorial turístico de VRD.
 *
 * Sustituye columnas inline por idioma. Una fila por (item_type, item_id, locale, field).
 *
 * Generado por Sprint i18n turismo VRD - Día 1 (2026-04-29).
 */
class TraduccionContenido extends Model
{
    protected $table = "traducciones_contenido";

    protected $fillable = [
        "item_type",
        "item_id",
        "locale",
        "field",
        "content",
        "auto_translated",
        "reviewed_at",
    ];

    protected $casts = [
        "auto_translated" => "boolean",
        "reviewed_at" => "datetime",
    ];

    public static function locales(): array
    {
        return ["es", "en", "fr", "it", "de", "pt", "ru", "ja", "ko", "zh"];
    }

    public static function localesNoBase(): array
    {
        return array_values(array_diff(static::locales(), ["es"]));
    }
}
