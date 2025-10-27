<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debito extends Model
{
    protected $fillable = [
        'cliente_id',
        'descricao',
        'tipo',
        'valor',
        'data',
    ];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
