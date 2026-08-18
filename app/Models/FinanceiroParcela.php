<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceiroParcela extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescricao_id',
        'prescricao_semana_id',
        'nr_parcela',
        'valor_parcela',
        'valor_pago',
        'situacao',
        'dt_vencimento',
        'obs',
    ];

    protected $casts = [
        'valor_parcela' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'dt_vencimento' => 'date',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class);
    }

    public function semana()
    {
        return $this->belongsTo(PrescricaoSemana::class, 'prescricao_semana_id');
    }

    public function pagamentos()
    {
        return $this->hasMany(PagamentoParcela::class);
    }
}
