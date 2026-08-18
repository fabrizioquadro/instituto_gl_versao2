<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'clinica_id',
        'user_id',
        'fornecedor_id',
        'nota',
        'data',
        'valor',
        'arquivo',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function movimentos()
    {
        return $this->hasMany(Estoque::class, 'entrada_id');
    }
}
