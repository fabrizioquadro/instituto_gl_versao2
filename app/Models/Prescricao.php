<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescricao extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_versao1',
        'paciente_id',
        'clinica_id',
        'user_id_cadastro',
        'medico',
        'tipo_atendimento',
        'agendamento',
        'data_prescricao',
        'qt_semanas',
        'qt_semanas_aplicacao',
        'qt_parcelas',
        'periodicidade_dias',
        'semana_atual',
        'valor_tratamento',
        'credito_em_aberto',
        'situacao',
        'situacao_financeira',
        'obs',
    ];

    protected $casts = [
        'data_prescricao' => 'date',
        'valor_tratamento' => 'decimal:2',
        'credito_em_aberto' => 'decimal:2',
        'periodicidade_dias' => 'integer',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function userCadastro()
    {
        return $this->belongsTo(User::class, 'user_id_cadastro');
    }

    public function semanas()
    {
        return $this->hasMany(PrescricaoSemana::class);
    }

    public function financeiroParcelas()
    {
        return $this->hasMany(FinanceiroParcela::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(PrescricaoPagamento::class);
    }

    public function anexos()
    {
        return $this->hasMany(Anexo::class)->where('tipo', 'prescricao');
    }

    public function logs()
    {
        return $this->hasMany(PrescricaoLog::class);
    }

    public function observacoes()
    {
        return $this->hasMany(PrescricaoObservacao::class);
    }
}
