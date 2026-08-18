<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Soro extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
    ];

    public function medicamentos()
    {
        return $this->hasMany(SoroMedicamento::class);
    }
}
