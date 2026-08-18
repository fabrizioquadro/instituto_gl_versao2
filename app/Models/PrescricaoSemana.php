<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoSemana extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'prescricao_id',
        'nr_semana',
        'data_prevista',
        'tem_aplicacao',
        'situacao',
        'obs',
        'dt_hr_chegada',
        'dt_hr_atendimento',
        'dt_hr_finalizacao',
        'user_id_aplicacao',
        'autorizador_sem_pagamento',
    ];

    protected $casts = [
        'data_prevista' => 'date',
        'tem_aplicacao' => 'boolean',
        'dt_hr_chegada' => 'datetime',
        'dt_hr_atendimento' => 'datetime',
        'dt_hr_finalizacao' => 'datetime',
    ];

    public function prescricao()
    {
        return $this->belongsTo(Prescricao::class);
    }

    public function medicamentos()
    {
        return $this->hasMany(PrescricaoSemanaMedicamento::class);
    }

    public function financeiroParcela()
    {
        return $this->hasOne(FinanceiroParcela::class);
    }

    public function userAplicacao()
    {
        return $this->belongsTo(User::class, 'user_id_aplicacao');
    }

    public function autorizador()
    {
        return $this->belongsTo(User::class, 'autorizador_sem_pagamento');
    }
}
