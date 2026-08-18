<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'paciente_id_feegow',
        'nm_paciente',
        'dt_nascimento',
        'cpf',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'telefone',
        'email',
        'obs',
        'st_google',
        'ativo',
        'sincronizado_em',
    ];

    /**
     * Apenas pacientes visíveis (os desativados ficam só no BD p/ histórico).
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
