<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anexo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'prescricao_id',
        'pagamento_id',
        'user_id',
        'nm_anexo',
        'arquivo',
        'mime',
        'extensao',
        'visualizado_em',
        'visualizado_por',
    ];

    protected $casts = [
        'visualizado_em' => 'datetime',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class);
    }

    public function pagamento()
    {
        return $this->belongsTo(PrescricaoPagamento::class, 'pagamento_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visualizadoPor()
    {
        return $this->belongsTo(User::class, 'visualizado_por');
    }
}
