<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AplicacaoLote extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'prescricao_semana_medicamento_id',
        'quantidade',
        'lote',
        'codigo_barras',
        'estoque_aberto_id',
    ];

    public function prescricaoSemanaMedicamento()
    {
        return $this->belongsTo(PrescricaoSemanaMedicamento::class);
    }

    public function estoqueAberto()
    {
        return $this->belongsTo(EstoqueAberto::class);
    }
}
