<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagamentoParcela extends Model
{
    use HasFactory;

    protected $fillable = [
        'pagamento_id',
        'financeiro_parcela_id',
        'valor',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function pagamento()
    {
        return $this->belongsTo(PrescricaoPagamento::class, 'pagamento_id');
    }

    public function financeiroParcela()
    {
        return $this->belongsTo(FinanceiroParcela::class);
    }
}
