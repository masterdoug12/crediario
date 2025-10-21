<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $fillable = [
        'nome',
        'telefone',
        'endereco',
    ];

    /**
     * Débitos associados ao cliente.
     */
    public function debitos(): HasMany
    {
        return $this->hasMany(Debito::class);
    }

    /**
     * Pagamentos associados ao cliente.
     */
    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    /**
     * Retorna o saldo atual calculado dinamicamente.
     */
    public function getSaldoAtualAttribute(): float
    {
        $totalDebitos = $this->debitos()->sum('valor');
        $totalPagamentos = $this->pagamentos()->sum('valor');

        return (float) $totalDebitos - (float) $totalPagamentos;
    }
}
