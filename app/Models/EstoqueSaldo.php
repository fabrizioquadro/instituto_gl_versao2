<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstoqueSaldo extends Model
{
    use HasFactory;

    protected $table = 'estoques_saldos';

    protected $fillable = [
        'clinica_id',
        'medicamento_id',
        'lote',
        'codigo_barras',
        'dt_vencimento',
        'saldo',
    ];

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }
}
