<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    use HasFactory;

    protected $table = 'fornecedores';

    protected $fillable = [
        'id_versao1',
        'nome',
        'cnpj',
        'email',
        'tel',
        'cel',
        'situacao',
    ];

    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }
}
