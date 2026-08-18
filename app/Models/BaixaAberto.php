<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaixaAberto extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'clinica_id',
        'estoque_aberto_id',
        'user_id',
        'quantidade',
        'motivo',
    ];

    public function estoqueAberto()
    {
        return $this->belongsTo(EstoqueAberto::class, 'estoque_aberto_id');
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
