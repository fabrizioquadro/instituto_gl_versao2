<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescricaoSemanaMedicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'prescricao_semana_id',
        'medicamento_id',
        'combo_id',
        'soro_id',
        'clinica_id_aplicacao',
        'is_soro',
        'gera_aplicacao',
        'quantidade',
        'situacao',
        'data_prevista',
        'dt_hr_chegada',
        'dt_hr_atendimento',
        'aplicado_em',
        'user_id_aplicacao',
        'obs',
        'enviado_feegow',
    ];

    protected $casts = [
        'is_soro' => 'boolean',
        'gera_aplicacao' => 'boolean',
        'enviado_feegow' => 'boolean',
        'data_prevista' => 'date',
        'dt_hr_chegada' => 'datetime',
        'dt_hr_atendimento' => 'datetime',
        'aplicado_em' => 'datetime',
    ];

    public function semana()
    {
        return $this->belongsTo(PrescricaoSemana::class, 'prescricao_semana_id');
    }

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }

    public function soro()
    {
        return $this->belongsTo(Soro::class);
    }

    public function clinicaAplicacao()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id_aplicacao');
    }

    public function userAplicacao()
    {
        return $this->belongsTo(User::class, 'user_id_aplicacao');
    }

    public function lotes()
    {
        return $this->hasMany(AplicacaoLote::class, 'prescricao_semana_medicamento_id');
    }

    /**
     * Exibição dos lotes consumidos nesta aplicação (HTML).
     */
    public function lotesDisplay(): string
    {
        $lotes = $this->lotes;
        if ($lotes->isEmpty()) {
            return '';
        }
        if ($lotes->count() > 1) {
            return $lotes->map(fn ($l) => 'Lote: '.$l->lote.', Qtd: '.$l->quantidade)->implode('<br>');
        }

        return 'Lote: '.$lotes->first()->lote;
    }

    /**
     * Exibição dos códigos de barras consumidos nesta aplicação (HTML).
     */
    public function codigosDisplay(): string
    {
        $lotes = $this->lotes;
        if ($lotes->isEmpty()) {
            return '';
        }
        if ($lotes->count() > 1) {
            return $lotes->map(fn ($l) => 'Código: '.$l->codigo_barras.', Qtd: '.$l->quantidade)->implode('<br>');
        }

        return 'Código: '.$lotes->first()->codigo_barras;
    }

    /**
     * Data de vencimento dos lotes consumidos (HTML).
     */
    public function vencimentosDisplay(): string
    {
        $lotes = $this->lotes;
        if ($lotes->isEmpty()) {
            return '';
        }
        $datas = [];
        foreach ($lotes as $l) {
            // O vencimento é uma propriedade do lote/código e fica gravado nas
            // movimentações de estoque (estoques.dt_vencimento). O estoques_saldos
            // é deletado quando o saldo zera, então NÃO é fonte confiável aqui.
            $vencimento = \App\Models\Estoque::where('codigo_barras', $l->codigo_barras)
                ->where('lote', $l->lote)
                ->whereNotNull('dt_vencimento')
                ->orderBy('id')
                ->value('dt_vencimento');
            $datas[] = $vencimento ? dataDbForm($vencimento) : '-';
        }

        return implode('<br>', $datas);
    }
}
