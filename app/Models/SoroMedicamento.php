<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoroMedicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'soro_id',
        'medicamento_id',
        'quantidade',
        'valor_unitario',
    ];

    public function soro()
    {
        return $this->belongsTo(Soro::class);
    }

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }
}
