<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinica extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'nome',
        'cnpj',
        'id_unidade_feegow',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
