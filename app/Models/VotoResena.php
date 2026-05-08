<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VotoResena extends Model
{
    use HasFactory;

    protected $table = 'votos_resena';

    protected $fillable = [
        'resena_restaurante_id',
        'usuario_id',
        'util',
    ];

    protected $casts = [
        'util' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeUtiles($query)
    {
        return $query->where('util', true);
    }

    public function scopeNoUtiles($query)
    {
        return $query->where('util', false);
    }
}
