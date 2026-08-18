<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstoqueAberto extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'medicamento_id',
        'procedimento_id',
        'user_id',
        'clinica_id',
        'dt_cadastro',
        'qt_inicial',
        'qt_utilizado',
        'qt_restante',
        'lote',
        'codigo_barras',
        'situacao',
    ];

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }
}
