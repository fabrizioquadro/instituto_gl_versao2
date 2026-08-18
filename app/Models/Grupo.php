<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'nome',
    ];

    public function medicamentos()
    {
        return $this->hasMany(Medicamento::class);
    }
}
