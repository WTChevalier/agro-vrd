<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Cache key for settings
    protected static string $cacheKey = 'app_settings';
    protected static int $cacheTtl = 3600; // 1 hora

    // ========== Accessors ==========

    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->type) {
                    'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
                    'integer', 'int' => (int) $this->value,
                    'float', 'double' => (float) $this->value,
                    'array', 'json' => json_decode($this->value, true) ?? [],
                    default => $this->value,
                };
            }
        );
    }

    protected function fullKey(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->group ? "{$this->group}.{$this->key}" : $this->key
        );
    }

    // ========== Scopes ==========

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    // ========== Static Helpers ==========

    public static function get(string $key, $default = null)
    {
        $settings = static::getAllCached();

        // Soportar notacion de punto (ej: "general.site_name")
        if (str_contains($key, '.')) {
            [$group, $settingKey] = explode('.', $key, 2);
            $fullKey = "{$group}.{$settingKey}";
        } else {
            $fullKey = $key;
        }

        return $settings[$fullKey] ?? $default;
    }

    public static function set(string $key, $value, ?string $type = null, ?string $group = null): self
    {
        // Parsear clave con notacion de punto
        if (str_contains($key, '.') && !$group) {
            [$group, $key] = explode('.', $key, 2);
        }

        // Determinar tipo automaticamente si no se especifica
        if (!$type) {
            $type = match(true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_float($value) => 'float',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        // Convertir valor a string para almacenar
        $stringValue = match($type) {
            'boolean', 'bool' => $value ? '1' : '0',
            'array', 'json' => json_encode($value),
            default => (string) $value,
        };

        $setting = static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stringValue, 'type' => $type]
        );

        static::clearCache();

        return $setting;
    }

    public static function getByGroup(string $group): array
    {
        $settings = static::getAllCached();

        return collect($settings)
            ->filter(fn ($value, $key) => str_starts_with($key, "{$group}."))
            ->mapWithKeys(fn ($value, $key) => [
                str_replace("{$group}.", '', $key) => $value
            ])
            ->toArray();
    }

    public static function getAllCached(): array
    {
        return Cache::remember(static::$cacheKey, static::$cacheTtl, function () {
            return static::all()
                ->mapWithKeys(fn ($setting) => [
                    $setting->full_key => $setting->typed_value
                ])
                ->toArray();
        });
    }

    public static function getPublicSettings(): array
    {
        return Cache::remember(static::$cacheKey . '_public', static::$cacheTtl, function () {
            return static::public()
                ->get()
                ->mapWithKeys(fn ($setting) => [
                    $setting->full_key => $setting->typed_value
                ])
                ->toArray();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(static::$cacheKey);
        Cache::forget(static::$cacheKey . '_public');
    }

    // ========== Helpers de Configuracion Comun ==========

    public static function getSiteName(): string
    {
        return static::get('general.site_name', 'SazonRD');
    }

    public static function getDeliveryFee(): float
    {
        return (float) static::get('delivery.base_fee', 100);
    }

    public static function getServiceFeePercentage(): float
    {
        return (float) static::get('fees.service_percentage', 5);
    }

    public static function getTaxPercentage(): float
    {
        return (float) static::get('fees.tax_percentage', 18);
    }

    public static function getMinimumOrder(): float
    {
        return (float) static::get('order.minimum_amount', 300);
    }

    public static function getLoyaltyPointsPerPeso(): float
    {
        return (float) static::get('loyalty.points_per_peso', 1);
    }

    public static function getPointsRedemptionRate(): float
    {
        return (float) static::get('loyalty.redemption_rate', 100); // 100 puntos = 1 peso
    }
}
