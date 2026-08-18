<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoPagamentoForma extends Model
{
    use HasFactory;

    protected $fillable = [
        'pagamento_id',
        'forma_pagamento',
        'vl_pagamento',
        'parcelas',
        'id_transacao',
        'obs',
    ];

    protected $casts = [
        'vl_pagamento' => 'decimal:2',
    ];

    public function pagamento()
    {
        return $this->belongsTo(PrescricaoPagamento::class, 'pagamento_id');
    }
}
