<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoPagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescricao_id',
        'dt_pagamento',
        'vl_total',
        'obs',
        'user_id',
    ];

    protected $casts = [
        'dt_pagamento' => 'date',
        'vl_total' => 'decimal:2',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function formas()
    {
        return $this->hasMany(PrescricaoPagamentoForma::class, 'pagamento_id');
    }

    public function parcelasPagas()
    {
        return $this->hasMany(PagamentoParcela::class, 'pagamento_id');
    }

    public function anexos()
    {
        return $this->hasMany(Anexo::class, 'pagamento_id')->where('tipo', 'comprovante_pagamento');
    }
}
