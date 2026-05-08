<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'payment_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // ========== Accessors ==========

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                'credit' => 'Recarga',
                'debit' => 'Pago',
                'refund' => 'Reembolso',
                'bonus' => 'Bonus',
                'cashback' => 'Cashback',
                'transfer_in' => 'Transferencia Recibida',
                'transfer_out' => 'Transferencia Enviada',
                'adjustment' => 'Ajuste',
                default => $this->type,
            }
        );
    }

    protected function isCredit(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->type, ['credit', 'refund', 'bonus', 'cashback', 'transfer_in'])
        );
    }

    protected function isDebit(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->type, ['debit', 'transfer_out'])
        );
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $sign = $this->is_credit ? '+' : '-';
                return $sign . 'RD$ ' . number_format($this->amount, 2);
            }
        );
    }

    protected function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->balance_after, 2)
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->status) {
                'pending' => 'Pendiente',
                'completed' => 'Completado',
                'failed' => 'Fallido',
                'cancelled' => 'Cancelado',
                default => $this->status ?? 'Completado',
            }
        );
    }

    // ========== Scopes ==========

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCredits($query)
    {
        return $query->whereIn('type', ['credit', 'refund', 'bonus', 'cashback', 'transfer_in']);
    }

    public function scopeDebits($query)
    {
        return $query->whereIn('type', ['debit', 'transfer_out']);
    }

    public function scopeCompleted($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'completed')
                ->orWhereNull('status');
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========== Helpers ==========

    public static function addFunds(User $user, float $amount, string $description, ?int $paymentId = null): self
    {
        $transaction = static::create([
            'user_id' => $user->id,
            'payment_id' => $paymentId,
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $user->wallet_balance + $amount,
            'description' => $description,
            'status' => 'completed',
        ]);

        $user->increment('wallet_balance', $amount);

        return $transaction;
    }

    public static function deductFunds(User $user, float $amount, string $description, ?int $orderId = null): self
    {
        $transaction = static::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $user->wallet_balance - $amount,
            'description' => $description,
            'status' => 'completed',
        ]);

        $user->decrement('wallet_balance', $amount);

        return $transaction;
    }

    public static function refund(User $user, float $amount, string $description, ?int $orderId = null): self
    {
        $transaction = static::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'type' => 'refund',
            'amount' => $amount,
            'balance_after' => $user->wallet_balance + $amount,
            'description' => $description,
            'status' => 'completed',
        ]);

        $user->increment('wallet_balance', $amount);

        return $transaction;
    }

    public static function addCashback(User $user, float $amount, string $description, ?int $orderId = null): self
    {
        $transaction = static::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'type' => 'cashback',
            'amount' => $amount,
            'balance_after' => $user->wallet_balance + $amount,
            'description' => $description,
            'status' => 'completed',
        ]);

        $user->increment('wallet_balance', $amount);

        return $transaction;
    }
}
