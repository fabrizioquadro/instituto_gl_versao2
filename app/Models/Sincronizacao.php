<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sincronizacao extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'data_inicio',
        'data_fim',
        'status',
        'criados',
        'atualizados',
        'erros',
        'detalhes',
    ];
}
