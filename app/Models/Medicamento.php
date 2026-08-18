<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'grupo_id',
        'nome',
        'fabricante',
        'tipo',
        'vasilhame',
        'ultimo_valor_pg',
        'vl_venda',
        'estoque_minimo',
        'estoque_medio',
        'situacao',
        'aplicacao',
        'aplicacao_feegow_id',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function comboMedicamentos()
    {
        return $this->hasMany(ComboMedicamento::class);
    }

    /**
     * Medicamento considerado "FERRO" (regra: nome contém "ferro"),
     * usado para alertas na recepção e aplicação + cor vermelha.
     */
    public function ehFerro(): bool
    {
        return mb_stripos((string) $this->nome, 'ferro') !== false;
    }
}
