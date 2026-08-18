<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Baixa extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'clinica_id',
        'user_id',
        'motivo',
        'data',
        'valor',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movimentos()
    {
        return $this->hasMany(Estoque::class, 'baixa_id');
    }
}
