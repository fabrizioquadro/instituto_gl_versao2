<?php

namespace App\Http\Controllers;

use App\Models\Baixa;
use App\Models\Estoque;
use App\Models\EstoqueSaldo;
use App\Models\Medicamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaixaSistemaController extends Controller
{
    /**
     * Lista as baixas da clínica do usuário logado.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Baixa::with(['user', 'clinica'])->where('clinica_id', $user->clinica_id);

        if ($request->filled('medicamento_id')) {
            $query->whereHas('movimentos', fn ($q) => $q->where('medicamento_id', (int) $request->medicamento_id));
        }

        $baixas = $query->orderByDesc('data')->get();
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome']);

        return view('estoque.baixas.index', compact('baixas', 'medicamentos'));
    }

    /**
     * Formulário para adicionar baixa.
     */
    public function create()
    {
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('estoque.baixas.create', compact('medicamentos'));
    }

    /**
     * Salva uma nova baixa com seus medicamentos (movimentos tipo Saida).
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'data' => 'required|date',
            'motivo_baixa' => 'required|string|max:255',
        ]);

        $motivo = $request->motivo_baixa;
        if ($request->observacao) {
            $motivo .= ' - '.$request->observacao;
        }

        try {
            return DB::transaction(function () use ($request, $user, $motivo) {
                $baixa = Baixa::create([
                    'clinica_id' => $user->clinica_id,
                    'user_id' => $user->id,
                    'motivo' => $motivo,
                    'data' => $request->data,
                    'valor' => 0,
                ]);

                $totalBaixa = 0;
                $contador = (int) $request->contador_medicamentos;

                for ($i = 1; $i <= $contador; $i++) {
                    $medicamentoId = $request->get("medicamento_id_{$i}");
                    if (! $medicamentoId) {
                        continue;
                    }

                    $lote = $request->get("lote_{$i}");
                    $quantidade = (float) $request->get("quantidade_{$i}");
                    $codigoBarras = $request->get("codigo_barras_{$i}");

                    if (! $lote || $quantidade <= 0) {
                        continue;
                    }

                    $medicamento = Medicamento::find($medicamentoId);
                    $disponivel = $this->saldoDisponivel($user->clinica_id, $medicamentoId, $lote, $codigoBarras);

                    if ($quantidade > $disponivel) {
                        throw new \Exception("Quantidade da baixa de {$medicamento->nome} excede o saldo disponível (lote {$lote}: {$disponivel}).");
                    }

                    $total = round($quantidade * (float) $medicamento->ultimo_valor_pg, 2);
                    $totalBaixa += $total;

                    $vencimento = $this->vencimentoDoLote($user->clinica_id, $medicamento->id, $lote, $codigoBarras);

                    // Bloqueia baixa de lote vencido
                    if ($vencimento && \Carbon\Carbon::parse($vencimento)->startOfDay() < now()->startOfDay()) {
                        throw new \Exception("Não é possível dar baixa no lote {$lote} de {$medicamento->nome}: vencido em ".dataDbForm($vencimento).'.');
                    }

                    Estoque::registrar([
                        'clinica_id' => $baixa->clinica_id,
                        'baixa_id' => $baixa->id,
                        'medicamento_id' => $medicamento->id,
                        'user_id' => $user->id,
                        'origem' => 'Baixa',
                        'tipo' => 'Saida',
                        'quantidade' => $quantidade,
                        'valor' => $medicamento->ultimo_valor_pg,
                        'total' => $total,
                        'lote' => $lote,
                        'dt_vencimento' => $vencimento,
                        'codigo_barras' => $codigoBarras,
                    ]);
                }

                $baixa->update(['valor' => $totalBaixa]);

                return redirect()->route('estoque.baixas.show', $baixa->id)
                    ->with('mensagem', 'Baixa cadastrada com sucesso.')
                    ->with('abrir_espelho', true);
            });
        } catch (\Exception $e) {
            return redirect()->route('estoque.baixas.index')->with('mensagem_erro', 'Erro ao cadastrar baixa: '.$e->getMessage());
        }
    }

    /**
     * Exibe uma baixa.
     */
    public function show($id)
    {
        $baixa = Baixa::with(['user', 'clinica', 'movimentos.medicamento'])->findOrFail($id);

        return view('estoque.baixas.show', compact('baixa'));
    }

    /**
     * Espelho (listagem de confirmação) da baixa, para impressão.
     */
    public function espelho($id)
    {
        $baixa = Baixa::with(['user', 'clinica', 'movimentos.medicamento'])->findOrFail($id);

        return view('estoque.baixas.espelho', compact('baixa'));
    }

    /**
     * Exclui uma baixa (estorna os movimentos).
     */
    public function destroy($id)
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('estoque.baixas.index')->with('mensagem_erro', 'Somente administradores podem excluir baixas.');
        }

        $baixa = Baixa::with('movimentos')->findOrFail($id);

        try {
            DB::transaction(function () use ($baixa) {
                foreach ($baixa->movimentos as $mov) {
                    Estoque::remover($mov);
                }
                $baixa->delete();
            });

            return redirect()->route('estoque.baixas.index')->with('mensagem', 'Baixa excluída com sucesso.');
        } catch (\Exception $e) {
            return redirect()->route('estoque.baixas.index')->with('mensagem_erro', 'Erro ao excluir baixa: '.$e->getMessage());
        }
    }

    /**
     * Lotes com saldo de um medicamento na clínica (Ajax).
     */
    public function getLotes(Request $request)
    {
        $user = auth()->user();
        $clinicaId = $request->filled('clinica_id') ? $request->clinica_id : $user->clinica_id;

        $lotes = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $request->medicamento_id)
            ->get();

        return response()->json(['lotes' => $lotes]);
    }

    /**
     * Saldo disponível de (medicamento, lote, código) na clínica.
     */
    private function saldoDisponivel($clinicaId, $medicamentoId, $lote, $codigoBarras): float
    {
        $query = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $medicamentoId)
            ->where('lote', $lote);

        if ($codigoBarras) {
            $query->where('codigo_barras', $codigoBarras);
        }

        return (float) $query->sum('saldo');
    }

    /**
     * Data de vencimento do lote/código (para registrar na baixa).
     */
    private function vencimentoDoLote($clinicaId, $medicamentoId, $lote, $codigoBarras)
    {
        $query = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $medicamentoId)
            ->where('lote', $lote);

        if ($codigoBarras) {
            $query->where('codigo_barras', $codigoBarras);
        }

        return $query->value('dt_vencimento');
    }
}
