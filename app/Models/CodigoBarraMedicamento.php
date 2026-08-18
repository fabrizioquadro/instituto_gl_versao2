<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoBarraMedicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'medicamento_id',
        'contador',
    ];

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }
}
