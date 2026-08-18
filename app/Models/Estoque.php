<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Estoque extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_versao1',
        'clinica_id',
        'entrada_id',
        'baixa_id',
        'transferencia_id',
        'procedimento_id',
        'medicamento_id',
        'user_id',
        'origem',
        'tipo',
        'quantidade',
        'valor',
        'total',
        'lote',
        'dt_vencimento',
        'codigo_barras',
        'motivo',
    ];

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class);
    }

    public function entrada()
    {
        return $this->belongsTo(Entrada::class);
    }

    public function baixa()
    {
        return $this->belongsTo(Baixa::class);
    }

    public function transferencia()
    {
        return $this->belongsTo(Transferencia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra um movimento de estoque e atualiza o saldo persistido
     * (estoques_saldos) na mesma transação.
     */
    public static function registrar(array $dados): self
    {
        return DB::transaction(function () use ($dados) {
            $movimento = self::create($dados);

            $delta = (float) $dados['quantidade'] * (($dados['tipo'] ?? 'Entrada') === 'Saida' ? -1 : 1);
            self::atualizarSaldo(
                $dados['clinica_id'],
                $dados['medicamento_id'],
                $dados['lote'],
                $dados['codigo_barras'] ?? null,
                $dados['dt_vencimento'] ?? null,
                $delta
            );

            return $movimento;
        });
    }

    /**
     * Remove um movimento (estorno simples) e reverte o saldo persistido.
     */
    public static function remover(self $movimento): void
    {
        DB::transaction(function () use ($movimento) {
            $delta = (float) $movimento->quantidade * ($movimento->tipo === 'Saida' ? 1 : -1);
            self::atualizarSaldo(
                $movimento->clinica_id,
                $movimento->medicamento_id,
                $movimento->lote,
                $movimento->codigo_barras,
                $movimento->dt_vencimento,
                $delta
            );
            $movimento->delete();
        });
    }

    /**
     * Aplica um delta (+) ou (-) ao saldo de (clínica, medicamento, lote, código).
     * Cria a linha de saldo quando necessário e remove quando zera.
     */
    private static function atualizarSaldo($clinicaId, $medicamentoId, $lote, $codigoBarras, $dtVencimento, float $delta): void
    {
        $query = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $medicamentoId)
            ->where('lote', $lote);

        if ($codigoBarras === null || $codigoBarras === '') {
            $query->whereNull('codigo_barras');
        } else {
            $query->where('codigo_barras', $codigoBarras);
        }

        $saldo = $query->first();

        if (! $saldo) {
            EstoqueSaldo::create([
                'clinica_id' => $clinicaId,
                'medicamento_id' => $medicamentoId,
                'lote' => $lote,
                'codigo_barras' => $codigoBarras ?: null,
                'dt_vencimento' => $dtVencimento,
                'saldo' => $delta,
            ]);

            return;
        }

        $novoSaldo = (float) $saldo->saldo + $delta;

        if ($novoSaldo <= 0) {
            $saldo->delete();
        } else {
            $saldo->saldo = $novoSaldo;
            if ($dtVencimento && ! $saldo->dt_vencimento) {
                $saldo->dt_vencimento = $dtVencimento;
            }
            $saldo->save();
        }
    }
}
