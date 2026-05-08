<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_method_id',
        'provider',
        'provider_transaction_id',
        'method',
        'amount',
        'currency',
        'status',
        'error_message',
        'metadata',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // ========== Relaciones ==========

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // ========== Accessors ==========

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->amount, 2)
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->status) {
                'pending' => 'Pendiente',
                'processing' => 'Procesando',
                'completed' => 'Completado',
                'failed' => 'Fallido',
                'refunded' => 'Reembolsado',
                'partially_refunded' => 'Reembolso Parcial',
                'cancelled' => 'Cancelado',
                default => $this->status,
            }
        );
    }

    protected function providerLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->provider) {
                'cardnet' => 'CardNet',
                'azul' => 'Azul',
                'paypal' => 'PayPal',
                'wallet' => 'Wallet SazonRD',
                'cash' => 'Efectivo',
                'transfer' => 'Transferencia',
                default => $this->provider,
            }
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'completed'
        );
    }

    protected function isRefunded(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status, ['refunded', 'partially_refunded'])
        );
    }

    protected function canBeRefunded(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'completed' && !$this->refunded_at
        );
    }

    // ========== Scopes ==========

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', ['refunded', 'partially_refunded']);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ========== Helpers ==========

    public function markAsPaid(?string $transactionId = null): void
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
            'provider_transaction_id' => $transactionId ?? $this->provider_transaction_id,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function refund(float $amount, string $reason): void
    {
        $isFullRefund = $amount >= $this->amount;

        $this->update([
            'status' => $isFullRefund ? 'refunded' : 'partially_refunded',
            'refunded_at' => now(),
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ]);
    }
}
