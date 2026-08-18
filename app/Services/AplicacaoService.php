<?php

namespace App\Services;

use App\Models\AplicacaoLote;
use App\Models\Estoque;
use App\Models\EstoqueAberto;
use App\Models\EstoqueSaldo;
use App\Models\Medicamento;
use App\Models\PrescricaoLog;
use App\Models\PrescricaoSemana;
use App\Models\PrescricaoSemanaMedicamento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Regras de negócio do fluxo de aplicação (Fila de Atendimento + bipagem +
 * lançamento). Portado da V1, adaptado ao modelo de dados da V2.
 */
class AplicacaoService
{
    // =====================================================================
    // FILA DE ATENDIMENTO
    // =====================================================================

    /**
     * A semana está liberada para ir à fila? (parcela da semana paga OU sem parcela)
     */
    public function semanaLiberada(PrescricaoSemana $semana): bool
    {
        $parcela = $semana->financeiroParcela;
        if (! $parcela) {
            return true; // semana sem parcela financeira — liberada
        }

        return (float) $parcela->valor_pago >= (float) $parcela->valor_parcela;
    }

    /**
     * Envia a semana para a Fila de Aplicação.
     */
    public function enviarFila(PrescricaoSemana $semana, User $user, ?User $autorizador = null): void
    {
        $semana->situacao = 'Fila de Aplicação';
        $semana->dt_hr_chegada = now();
        $semana->user_id_aplicacao = null;
        $semana->autorizador_sem_pagamento = $autorizador?->id;
        $semana->save();

        PrescricaoLog::create([
            'prescricao_id' => $semana->prescricao_id,
            'entidade' => 'prescricao_semana',
            'entidade_id' => $semana->id,
            'user_id' => $user->id,
            'acao' => 'enviado_fila',
            'descricao' => $autorizador
                ? 'Semana '.$semana->nr_semana.'/'.$semana->prescricao->qt_semanas.' enviada para a fila SEM pagamento (autorizado por '.$autorizador->nome.').'
                : 'Semana '.$semana->nr_semana.'/'.$semana->prescricao->qt_semanas.' enviada para a fila de atendimento.',
        ]);
    }

    /**
     * Inicia o atendimento (situacao = Atendimento) marcando quem está atendendo.
     */
    public function abrirAtendimento(PrescricaoSemana $semana, User $user): void
    {
        $semana->situacao = 'Atendimento';
        $semana->dt_hr_atendimento = now();
        $semana->user_id_aplicacao = $user->id;
        $semana->save();

        PrescricaoLog::create([
            'prescricao_id' => $semana->prescricao_id,
            'entidade' => 'prescricao_semana',
            'entidade_id' => $semana->id,
            'user_id' => $user->id,
            'acao' => 'iniciado_atendimento',
            'descricao' => 'Atendimento da semana '.$semana->nr_semana.' iniciado por '.$user->nome.'.',
        ]);
    }

    // =====================================================================
    // BIPAGEM (consultas AJAX)
    // =====================================================================

    /**
     * Ampola — valida código de barras no estoque fechado (EstoqueSaldo).
     * Considera o grupo: se o medicamento pertence a um grupo, aceita o código
     * de barras de QUALQUER medicamento do mesmo grupo (mesmo produto).
     */
    public function buscarLote(int $medicamentoId, int $clinicaId, string $codigoBarras, float $quantidade): array
    {
        $medicamento = Medicamento::findOrFail($medicamentoId);

        $saldo = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->whereIn('medicamento_id', $this->medicamentosDoGrupo($medicamento))
            ->where('codigo_barras', $codigoBarras)
            ->where('saldo', '>', 0)
            ->first();

        if (! $saldo) {
            return ['controle' => 'false', 'lote' => '', 'mensagem' => 'Código de barras inválido para este medicamento!'];
        }

        if ($saldo->dt_vencimento && $saldo->dt_vencimento < now()->toDateString()) {
            return [
                'controle' => 'vencido',
                'lote' => $saldo->lote,
                'mensagem' => 'Este medicamento está VENCIDO desde '.dataDbForm($saldo->dt_vencimento).'. Não é possível aplicar.',
            ];
        }

        if ((float) $saldo->saldo < $quantidade) {
            return ['controle' => 'insuficiente', 'lote' => '', 'mensagem' => 'Quantidade em estoque insuficiente!', 'saldo' => (float) $saldo->saldo];
        }

        return ['controle' => 'true', 'lote' => $saldo->lote, 'saldo' => (float) $saldo->saldo];
    }

    /**
     * Vasilhame — valida código de barras em um FRASCO ABERTO (EstoqueAberto).
     */
    public function buscarFrasco(Medicamento $medicamento, int $clinicaId, string $codigoBarras, float $quantidade): array
    {
        $query = EstoqueAberto::where('clinica_id', $clinicaId)
            ->where('codigo_barras', $codigoBarras)
            ->where('situacao', 'Aberto');

        $query->whereIn('medicamento_id', $this->medicamentosDoGrupo($medicamento));

        $frasco = $query->first();

        if (! $frasco) {
            return ['controle' => 'false', 'lote' => '', 'mensagem' => 'Código de Barras Inválido'];
        }

        // vencimento do lote original
        $saldo = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $frasco->medicamento_id)
            ->where('lote', $frasco->lote)
            ->where('codigo_barras', $codigoBarras)
            ->first();

        if ($saldo && $saldo->dt_vencimento && $saldo->dt_vencimento < now()->toDateString()) {
            return [
                'controle' => 'vencido',
                'lote' => $frasco->lote,
                'mensagem' => 'Este medicamento está VENCIDO desde '.dataDbForm($saldo->dt_vencimento).'. Não é possível aplicar.',
            ];
        }

        if ((float) $frasco->qt_restante < $quantidade) {
            return [
                'controle' => 'false',
                'lote' => '',
                'mensagem' => 'Este frasco não possui a quantidade necessária para esta aplicação. Use a aplicação com 2 códigos.',
            ];
        }

        return ['controle' => 'true', 'lote' => $frasco->lote, 'saldo' => (float) $frasco->qt_restante];
    }

    /**
     * Vasilhame — lista códigos de barras com saldo para o modal "Abrir Frasco".
     */
    public function listarFrascosParaAbrir(int $medicamentoId, int $clinicaId)
    {
        return EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $medicamentoId)
            ->whereNotNull('codigo_barras')
            ->where('codigo_barras', '<>', '')
            ->where('saldo', '>', 0)
            ->orderBy('lote')
            ->get();
    }

    /**
     * Abre um frasco (Vasilhame): cria EstoqueAberto e "reserva" no estoque (Saida qtd 1).
     */
    public function abrirFrasco(int $medicamentoId, int $clinicaId, string $codigoBarras, User $user, int $semanaId): void
    {
        $medicamento = Medicamento::findOrFail($medicamentoId);

        $saldo = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $medicamentoId)
            ->where('codigo_barras', $codigoBarras)
            ->where('saldo', '>', 0)
            ->first();

        if (! $saldo) {
            throw new \Exception('Estoque não encontrado para o medicamento '.$medicamento->nome.' com o código de barras informado.');
        }

        if ($saldo->dt_vencimento && $saldo->dt_vencimento < now()->toDateString()) {
            throw new \Exception('O lote '.$saldo->lote.' do medicamento '.$medicamento->nome.' está vencido desde '.dataDbForm($saldo->dt_vencimento).' e não pode ser aberto.');
        }

        if (! $medicamento->vasilhame) {
            throw new \Exception('O medicamento '.$medicamento->nome.' não possui vasilhame definido para abrir frasco.');
        }

        DB::transaction(function () use ($medicamento, $saldo, $clinicaId, $codigoBarras, $user, $semanaId) {
            EstoqueAberto::create([
                'medicamento_id' => $medicamento->id,
                'procedimento_id' => $semanaId,
                'user_id' => $user->id,
                'clinica_id' => $clinicaId,
                'dt_cadastro' => now()->toDateString(),
                'qt_inicial' => $medicamento->vasilhame,
                'qt_utilizado' => 0,
                'qt_restante' => $medicamento->vasilhame,
                'lote' => $saldo->lote,
                'codigo_barras' => $codigoBarras,
                'situacao' => 'Aberto',
            ]);

            Estoque::registrar([
                'clinica_id' => $clinicaId,
                'procedimento_id' => $semanaId,
                'medicamento_id' => $medicamento->id,
                'user_id' => $user->id,
                'origem' => 'FrascoAberto',
                'tipo' => 'Saida',
                'quantidade' => 1,
                'valor' => 0,
                'total' => 0,
                'lote' => $saldo->lote,
                'dt_vencimento' => $saldo->dt_vencimento,
                'codigo_barras' => $codigoBarras,
            ]);
        });
    }

    // =====================================================================
    // LANÇAMENTO DA APLICAÇÃO
    // =====================================================================

    /**
     * Lança a aplicação da semana: bipagem + baixa de estoque + situação.
     */
    public function lancar(PrescricaoSemana $semana, User $user, array $dados): void
    {
        DB::transaction(function () use ($semana, $user, $dados) {
            $pendente = false;

            foreach ($semana->medicamentos as $med) {
                if (! $med->gera_aplicacao) {
                    continue;
                }
                if (! in_array($med->situacao, ['Aberta', 'Pendente'])) {
                    continue;
                }

                $controlePendente = $dados['controle_pendente_'.$med->id] ?? null;
                if ($controlePendente === 'Sim') {
                    $pendente = true;
                    $med->situacao = 'Pendente';
                    $med->save();
                    continue;
                }

                $linhas = $this->linhasBipagem($med);
                $obs = $dados['obs_aplicacao'] ?? null;

                if (empty($linhas)) {
                    // soro/item sem componentes resolvíveis — só registra
                    $med->situacao = 'Aplicada';
                    $med->enviado_feegow = false;
                    $med->user_id_aplicacao = $user->id;
                    $med->obs = $obs;
                    $med->dt_hr_chegada = $semana->dt_hr_chegada;
                    $med->dt_hr_atendimento = $semana->dt_hr_atendimento;
                    $med->aplicado_em = now();
                    $med->save();
                    continue;
                }

                foreach ($linhas as $linha) {
                    $this->aplicarLinha($linha, $med, $user, $dados, $semana);
                }

                $med->situacao = 'Aplicada';
                $med->enviado_feegow = false;
                $med->user_id_aplicacao = $user->id;
                $med->obs = $obs;
                $med->dt_hr_chegada = $semana->dt_hr_chegada;
                $med->dt_hr_atendimento = $semana->dt_hr_atendimento;
                $med->aplicado_em = now();
                $med->save();
            }

            $this->recalcularSituacao($semana, $pendente);
        });
    }

    /**
     * Resolve as linhas de bipagem de uma medicação da semana:
     * medicamento único OU componentes de combo/soro.
     */
    private function linhasBipagem(PrescricaoSemanaMedicamento $med): array
    {
        if ($med->medicamento_id && $med->medicamento) {
            return [[
                'key' => (string) $med->id,
                'medicamento' => $med->medicamento,
                'quantidade' => (float) $med->quantidade,
            ]];
        }

        if ($med->combo_id && $med->combo) {
            $linhas = [];
            $i = 0;
            foreach ($med->combo->medicamentos as $cm) {
                if (! $cm->medicamento) {
                    continue;
                }
                $linhas[] = [
                    'key' => $med->id.'_'.$i,
                    'medicamento' => $cm->medicamento,
                    'quantidade' => (float) $cm->quantidade,
                ];
                $i++;
            }

            return $linhas;
        }

        if ($med->soro_id && $med->soro) {
            $linhas = [];
            $i = 0;
            foreach ($med->soro->medicamentos as $cm) {
                if (! $cm->medicamento) {
                    continue;
                }
                $linhas[] = [
                    'key' => $med->id.'_'.$i,
                    'medicamento' => $cm->medicamento,
                    'quantidade' => (float) $cm->quantidade,
                ];
                $i++;
            }

            return $linhas;
        }

        return [];
    }

    /**
     * Aplica (baixa) uma linha de bipagem conforme o tipo do medicamento.
     */
    private function aplicarLinha(array $linha, PrescricaoSemanaMedicamento $med, User $user, array $dados, PrescricaoSemana $semana): void
    {
        $medicamento = $linha['medicamento'];
        $key = $linha['key'];
        $quantidade = $linha['quantidade'];
        $clinicaId = $semana->prescricao->clinica_id;

        $codigoBarras = $dados['codigo_barras_'.$key] ?? '';
        $lote = $dados['lote_'.$key] ?? '';
        $controle = $dados['controle_med_'.$key] ?? '';

        if ($medicamento->tipo === 'Ampola') {
            if (empty($lote) || empty($codigoBarras)) {
                throw new \Exception('O campo Lote e Código de Barras são obrigatórios para a aplicação de '.$medicamento->nome);
            }

            $saldo = EstoqueSaldo::where('clinica_id', $clinicaId)
                ->whereIn('medicamento_id', $this->medicamentosDoGrupo($medicamento))
                ->where('lote', $lote)
                ->where('codigo_barras', $codigoBarras)
                ->first();

            if (! $saldo) {
                throw new \Exception('Estoque não encontrado para '.$medicamento->nome.' (lote '.$lote.').');
            }
            if ($saldo->dt_vencimento && $saldo->dt_vencimento < now()->toDateString()) {
                throw new \Exception('O lote '.$lote.' do medicamento '.$medicamento->nome.' está vencido desde '.dataDbForm($saldo->dt_vencimento).'.');
            }

            // Meia ampola: se a enfermagem confirmou "retirar 1 ampola inteira" para dose fracionada,
            // a aplicação mantém a dose (ex.: 0,5) mas o estoque dá baixa de 1 ampola inteira.
            $quantidadeBaixa = $quantidade;
            if (($dados['ampola_inteira_'.$key] ?? null) && $quantidade < 1) {
                $quantidadeBaixa = 1.0;
            }

            AplicacaoLote::create([
                'prescricao_semana_medicamento_id' => $med->id,
                'quantidade' => $quantidadeBaixa,
                'lote' => $lote,
                'codigo_barras' => $codigoBarras,
            ]);

            Estoque::registrar([
                'clinica_id' => $clinicaId,
                'procedimento_id' => $semana->id,
                'medicamento_id' => $saldo->medicamento_id, // produto REAL consumido (grupo)
                'user_id' => $user->id,
                'origem' => 'Aplicacao',
                'tipo' => 'Saida',
                'quantidade' => $quantidadeBaixa,
                'valor' => 0,
                'total' => 0,
                'lote' => $lote,
                'dt_vencimento' => $saldo->dt_vencimento,
                'codigo_barras' => $codigoBarras,
            ]);
        } elseif ($medicamento->tipo === 'Vasilhame') {
            if ($controle === '2_codigo') {
                $codigoB1 = $dados['cod_med_1_'.$key] ?? '';
                $codigoB2 = $dados['cod_med_2_'.$key] ?? '';
                $qtd1 = (float) ($dados['quant_med_1_'.$key] ?? 0);
                $qtd2 = (float) ($dados['quant_med_2_'.$key] ?? 0);

                if (empty($codigoB1) || empty($codigoB2)) {
                    throw new \Exception('O Código de Barras dos dois frascos são obrigatórios para a aplicação de '.$medicamento->nome);
                }

                $this->consumirFrasco($med, $semana, $codigoB1, $qtd1, $medicamento);
                $this->consumirFrasco($med, $semana, $codigoB2, $qtd2, $medicamento);
            } else {
                if (empty($lote) || empty($codigoBarras)) {
                    throw new \Exception('O campo Lote e Código de Barras são obrigatórios para a aplicação de '.$medicamento->nome);
                }

                $this->consumirFrasco($med, $semana, $codigoBarras, $quantidade, $medicamento);
            }
        } else {
            // Procedimento — sem lote/baixa
            $med->obs = $codigoBarras ?: ($med->obs ?? null);
        }
    }

    /**
     * Consome quantidade de um frasco aberto e registra o lote na aplicação.
     */
    private function consumirFrasco(PrescricaoSemanaMedicamento $med, PrescricaoSemana $semana, string $codigoBarras, float $quantidade, Medicamento $medicamentoLinha): void
    {
        if ($quantidade <= 0) {
            throw new \Exception('Quantidade inválida para a aplicação de '.$medicamentoLinha->nome.'.');
        }

        $clinicaId = $semana->prescricao->clinica_id;

        $query = EstoqueAberto::where('clinica_id', $clinicaId)
            ->where('codigo_barras', $codigoBarras)
            ->where('situacao', 'Aberto');

        $query->whereIn('medicamento_id', $this->medicamentosDoGrupo($medicamentoLinha));

        $frasco = $query->first();

        if (! $frasco) {
            throw new \Exception('Frasco aberto não encontrado para o código de barras '.$codigoBarras.'. Abra o frasco antes de aplicar.');
        }

        if ((float) $frasco->qt_restante < $quantidade) {
            throw new \Exception('O frasco '.$codigoBarras.' não possui quantidade suficiente (restam '.$frasco->qt_restante.').');
        }

        $frasco->qt_utilizado += $quantidade;
        $frasco->qt_restante -= $quantidade;
        if ($frasco->qt_restante <= 0) {
            $frasco->situacao = 'Finalizado';
        }
        $frasco->save();

        AplicacaoLote::create([
            'prescricao_semana_medicamento_id' => $med->id,
            'quantidade' => $quantidade,
            'lote' => $frasco->lote,
            'codigo_barras' => $frasco->codigo_barras,
            'estoque_aberto_id' => $frasco->id,
        ]);
    }

    /**
     * IDs de medicamentos considerados intercambiáveis na bipagem:
     * o próprio medicamento + todos os membros do mesmo grupo (mesmo produto).
     */
    private function medicamentosDoGrupo(Medicamento $medicamento): array
    {
        if ($medicamento->grupo_id) {
            $ids = Medicamento::where('grupo_id', $medicamento->grupo_id)->pluck('id')->all();
            if ($ids) {
                return $ids;
            }
        }

        return [$medicamento->id];
    }

    /**
     * Recalcula a situação da semana após o lançamento e atualiza a prescrição.
     */
    public function recalcularSituacao(PrescricaoSemana $semana, bool $pendente = false): void
    {
        $abertas = $semana->medicamentos()->whereIn('situacao', ['Aberta', 'Pendente'])->count();

        if ($abertas === 0 && in_array($semana->situacao, ['Atendimento', 'Fila de Aplicação', 'Aplicação Parcial', 'Pendente'])) {
            $semana->situacao = 'Aplicado';
            $semana->dt_hr_finalizacao = $semana->dt_hr_finalizacao ?? now();
        } elseif ($abertas > 0 && ($pendente || $abertas > 0)) {
            $semana->situacao = 'Aplicação Parcial';
        }
        $semana->save();

        // atualiza a prescrição
        $prescricao = $semana->prescricao;
        $aplicadas = $prescricao->semanas()->where('situacao', 'Aplicado')->count();
        $comAplicacao = $prescricao->semanas()->where('tem_aplicacao', true)->count();

        if ($comAplicacao > 0 && $aplicadas >= $comAplicacao) {
            $prescricao->situacao = 'Concluída';
        } elseif ($aplicadas > 0) {
            $prescricao->situacao = 'Em Andamento';
        }

        $semanaAtual = $prescricao->semanas()->whereIn('situacao', ['Atendimento', 'Aplicado', 'Aplicação Parcial'])->max('nr_semana');
        $prescricao->semana_atual = $semanaAtual ?: 0;
        $prescricao->save();

        PrescricaoLog::create([
            'prescricao_id' => $prescricao->id,
            'entidade' => 'prescricao_semana',
            'entidade_id' => $semana->id,
            'user_id' => auth()->id(),
            'acao' => 'situacao',
            'descricao' => 'Semana '.$semana->nr_semana.' situação: '.$semana->situacao,
        ]);
    }
}
