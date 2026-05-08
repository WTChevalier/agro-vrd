<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCombo extends Model
{
    use HasFactory;

    protected $table = 'items_combo';

    protected $fillable = [
        'combo_id',
        'plato_id',
        'cantidad',
        'obligatorio',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'obligatorio' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id');
    }

    public function plato()
    {
        return $this->belongsTo(Plato::class, 'plato_id');
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getSubtotalAttribute(): float
    {
        return $this->plato ? $this->plato->precio_final * $this->cantidad : 0;
    }
}
