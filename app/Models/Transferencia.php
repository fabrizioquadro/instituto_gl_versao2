<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'clinica_id',
        'clinica_destino_id',
        'user_id',
        'administrador_id',
        'motivo',
        'data',
        'valor',
    ];

    public function origem()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id');
    }

    public function destino()
    {
        return $this->belongsTo(Clinica::class, 'clinica_destino_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movimentos()
    {
        return $this->hasMany(Estoque::class, 'transferencia_id');
    }
}
