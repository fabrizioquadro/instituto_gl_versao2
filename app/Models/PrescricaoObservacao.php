<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoObservacao extends Model
{
    use HasFactory;

    protected $table = 'prescricao_observacoes';

    protected $fillable = [
        'prescricao_id',
        'user_id',
        'observacao',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
