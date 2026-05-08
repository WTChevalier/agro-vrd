<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'provincia_id',
        'municipio_id',
        'sector_id',
        'label',
        'address_line_1',
        'address_line_2',
        'reference',
        'latitude',
        'longitude',
        'delivery_instructions',
        'is_default',
        'is_verified',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            // Si es la primera direccion del usuario, hacerla default
            if (!static::where('user_id', $address->user_id)->exists()) {
                $address->is_default = true;
            }
        });
    }

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(GeoProvincia::class, 'provincia_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(GeoMunicipio::class, 'municipio_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(GeoSector::class, 'sector_id');
    }

    // ========== Accessors ==========

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->address_line_1,
                    $this->address_line_2,
                    $this->sector?->name,
                    $this->municipio?->name,
                    $this->provincia?->name,
                ]);
                return implode(', ', $parts);
            }
        );
    }

    protected function shortAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->address_line_1 . ($this->sector ? ', ' . $this->sector->name : '')
        );
    }

    protected function labelIcon(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->label) {
                'home', 'casa' => 'home',
                'work', 'trabajo', 'oficina' => 'briefcase',
                'partner', 'pareja' => 'heart',
                default => 'map-pin',
            }
        );
    }

    protected function hasCoordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->latitude !== null && $this->longitude !== null
        );
    }

    protected function displayLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->label ?? 'Direccion'
        );
    }

    // ========== Scopes ==========

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }

    public function scopeInMunicipio($query, int $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    // ========== Helpers ==========

    public function setAsDefault(): void
    {
        // Remover default de otras direcciones del usuario
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'sector' => $this->sector?->name,
            'municipio' => $this->municipio?->name,
            'provincia' => $this->provincia?->name,
            'reference' => $this->reference,
            'delivery_instructions' => $this->delivery_instructions,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'full_address' => $this->full_address,
        ];
    }

    public function toDeliveryAddress(): array
    {
        return [
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'sector' => $this->sector?->name,
            'sector_id' => $this->sector_id,
            'municipio' => $this->municipio?->name,
            'municipio_id' => $this->municipio_id,
            'provincia' => $this->provincia?->name,
            'provincia_id' => $this->provincia_id,
            'reference' => $this->reference,
            'delivery_instructions' => $this->delivery_instructions,
        ];
    }
}
