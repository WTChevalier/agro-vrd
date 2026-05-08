<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'provider_token',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'holder_name',
        'billing_address',
        'is_default',
        'is_verified',
        'metadata',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'metadata' => 'array',
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'card_exp_month' => 'integer',
        'card_exp_year' => 'integer',
    ];

    protected $hidden = [
        'provider_token',
    ];

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Accessors ==========

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->type === 'card') {
                    $brand = ucfirst($this->card_brand ?? 'Tarjeta');
                    return "{$brand} ****{$this->card_last_four}";
                }
                return match($this->type) {
                    'wallet' => 'Wallet SazonRD',
                    'paypal' => 'PayPal',
                    'bank_transfer' => 'Transferencia Bancaria',
                    default => ucfirst($this->type),
                };
            }
        );
    }

    protected function cardBrandIcon(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->card_brand) {
                'visa' => 'visa',
                'mastercard' => 'mastercard',
                'amex' => 'amex',
                'discover' => 'discover',
                default => 'credit-card',
            }
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->card_exp_month || !$this->card_exp_year) {
                    return false;
                }
                $expDate = now()->setYear($this->card_exp_year)->setMonth($this->card_exp_month)->endOfMonth();
                return $expDate->isPast();
            }
        );
    }

    protected function expirationDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->card_exp_month || !$this->card_exp_year) {
                    return null;
                }
                return sprintf('%02d/%d', $this->card_exp_month, $this->card_exp_year);
            }
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeCards($query)
    {
        return $query->where('type', 'card');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========== Helpers ==========

    public function setAsDefault(): void
    {
        // Remover default de otros metodos del usuario
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }
}
