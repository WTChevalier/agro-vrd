<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'attachments',
        'is_internal',
        'is_automated',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_internal' => 'boolean',
        'is_automated' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Accessors ==========

    protected function senderName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_automated) {
                    return 'Sistema';
                }
                return $this->user?->name ?? 'Usuario';
            }
        );
    }

    protected function senderType(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_automated) {
                    return 'system';
                }

                $ticket = $this->ticket;
                if (!$ticket) {
                    return 'unknown';
                }

                if ($this->user_id === $ticket->user_id) {
                    return 'customer';
                }

                return 'agent';
            }
        );
    }

    protected function senderTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->sender_type) {
                'customer' => 'Cliente',
                'agent' => 'Agente',
                'system' => 'Sistema',
                default => 'Desconocido',
            }
        );
    }

    protected function isRead(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->read_at !== null
        );
    }

    protected function hasAttachments(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->attachments)
        );
    }

    protected function attachmentUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attachments
                ? array_map(fn ($file) => asset('storage/' . $file), $this->attachments)
                : []
        );
    }

    protected function formattedMessage(): Attribute
    {
        return Attribute::make(
            get: fn () => nl2br(e($this->message))
        );
    }

    protected function timeAgo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }

    // ========== Scopes ==========

    public function scopeForTicket($query, int $ticketId)
    {
        return $query->where('support_ticket_id', $ticketId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeFromCustomer($query)
    {
        return $query->whereHas('ticket', function ($q) {
            $q->whereColumn('support_tickets.user_id', 'support_messages.user_id');
        });
    }

    public function scopeFromAgent($query)
    {
        return $query->whereHas('ticket', function ($q) {
            $q->whereColumn('support_tickets.user_id', '!=', 'support_messages.user_id');
        })->where('is_automated', false);
    }

    public function scopeAutomated($query)
    {
        return $query->where('is_automated', true);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    // ========== Helpers ==========

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public static function createAutomated(int $ticketId, string $message): self
    {
        return static::create([
            'support_ticket_id' => $ticketId,
            'user_id' => null,
            'message' => $message,
            'is_automated' => true,
        ]);
    }

    public function isFromCustomer(): bool
    {
        return $this->user_id === $this->ticket?->user_id;
    }

    public function isFromAgent(): bool
    {
        return !$this->is_automated && !$this->isFromCustomer();
    }
}
