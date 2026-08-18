<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Estoque;
use App\Models\EstoqueSaldo;
use App\Models\Medicamento;
use App\Models\Transferencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferenciaSistemaController extends Controller
{
    /**
     * Lista as transferências (origem ou destino = clínica do usuário).
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Transferencia::with(['origem', 'destino', 'user', 'movimentos.medicamento'])
            ->where('clinica_id', $user->clinica_id)
            ->orWhere('clinica_destino_id', $user->clinica_id);

        if ($request->filled('medicamento_id')) {
            $query->whereHas('movimentos', fn ($q) => $q->where('medicamento_id', (int) $request->medicamento_id));
        }

        $transferencias = $query->orderByDesc('data')->get();
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome']);

        return view('estoque.transferencias.index', compact('transferencias', 'user', 'medicamentos'));
    }

    /**
     * Formulário para adicionar transferência.
     */
    public function create()
    {
        $user = auth()->user();
        $clinicas = Clinica::where('id', '<>', $user->clinica_id)->orderBy('nome')->get();
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('estoque.transferencias.create', compact('clinicas', 'medicamentos', 'user'));
    }

    /**
     * Salva uma transferência: saída na origem + entrada no destino.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'clinica_destino_id' => 'required|integer|exists:clinicas,id',
            'clinica_origem_id' => 'required|integer|exists:clinicas,id',
            'data' => 'required|date',
            'motivo' => 'required|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request, $user) {
                $transferencia = Transferencia::create([
                    'clinica_id' => $request->clinica_origem_id,
                    'clinica_destino_id' => $request->clinica_destino_id,
                    'user_id' => $user->id,
                    'administrador_id' => $user->isAdmin() ? $user->id : null,
                    'motivo' => $request->motivo,
                    'data' => $request->data,
                    'valor' => 0,
                ]);

                $total = 0;
                $contador = (int) $request->contador_medicamentos;

                for ($i = 1; $i <= $contador; $i++) {
                    $medicamentoId = $request->get("medicamento_id_{$i}");
                    if (! $medicamentoId) {
                        continue;
                    }

                    $codigoBarras = $request->get("codigo_barras_{$i}");
                    $lote = $request->get("lote_{$i}");
                    $quantidade = (float) $request->get("quantidade_{$i}");

                    if (! $lote || $quantidade <= 0) {
                        continue;
                    }

                    $medicamento = Medicamento::find($medicamentoId);
                    $disponivel = $this->saldoDisponivel($transferencia->clinica_id, $medicamentoId, $lote, $codigoBarras);

                    if ($quantidade > $disponivel) {
                        throw new \Exception("Quantidade da transferência de {$medicamento->nome} excede o saldo na origem (lote {$lote}: {$disponivel}).");
                    }

                    $totalItem = round($quantidade * (float) $medicamento->ultimo_valor_pg, 2);
                    $total += $totalItem;
                    $vencimento = $this->vencimentoDoLote($transferencia->clinica_id, $medicamentoId, $lote, $codigoBarras);

                    // Bloqueia saída de lote vencido
                    if ($vencimento && \Carbon\Carbon::parse($vencimento)->startOfDay() < now()->startOfDay()) {
                        throw new \Exception("Não é possível transferir o lote {$lote} de {$medicamento->nome}: vencido em ".dataDbForm($vencimento).'.');
                    }

                    // saída na origem
                    Estoque::registrar([
                        'clinica_id' => $transferencia->clinica_id,
                        'transferencia_id' => $transferencia->id,
                        'medicamento_id' => $medicamento->id,
                        'user_id' => $user->id,
                        'origem' => 'Transferencia',
                        'tipo' => 'Saida',
                        'quantidade' => $quantidade,
                        'valor' => $medicamento->ultimo_valor_pg,
                        'total' => $totalItem,
                        'lote' => $lote,
                        'dt_vencimento' => $vencimento,
                        'codigo_barras' => $codigoBarras,
                    ]);

                    // entrada no destino (mesmo lote/código)
                    Estoque::registrar([
                        'clinica_id' => $transferencia->clinica_destino_id,
                        'transferencia_id' => $transferencia->id,
                        'medicamento_id' => $medicamento->id,
                        'user_id' => $user->id,
                        'origem' => 'Transferencia',
                        'tipo' => 'Entrada',
                        'quantidade' => $quantidade,
                        'valor' => $medicamento->ultimo_valor_pg,
                        'total' => $totalItem,
                        'lote' => $lote,
                        'dt_vencimento' => $vencimento,
                        'codigo_barras' => $codigoBarras,
                    ]);
                }

                $transferencia->update(['valor' => $total]);

                return redirect()->route('estoque.transferencias.show', $transferencia->id)
                    ->with('mensagem', 'Transferência cadastrada com sucesso.')
                    ->with('abrir_etiquetas', true);
            });
        } catch (\Exception $e) {
            return redirect()->route('estoque.transferencias.index')->with('mensagem_erro', 'Erro ao cadastrar transferência: '.$e->getMessage());
        }
    }

    /**
     * Exibe uma transferência.
     */
    public function show($id)
    {
        $transferencia = Transferencia::with(['origem', 'destino', 'user'])
            ->with(['movimentos' => fn ($q) => $q->with('medicamento')])
            ->findOrFail($id);

        $saidas = $transferencia->movimentos->where('tipo', 'Saida');
        $entradas = $transferencia->movimentos->where('tipo', 'Entrada');

        return view('estoque.transferencias.show', compact('transferencia', 'saidas', 'entradas'));
    }

    /**
     * Exclui uma transferência (estorna todos os movimentos).
     */
    public function destroy($id)
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('estoque.transferencias.index')->with('mensagem_erro', 'Somente administradores podem excluir transferências.');
        }

        $transferencia = Transferencia::with('movimentos')->findOrFail($id);

        try {
            DB::transaction(function () use ($transferencia) {
                foreach ($transferencia->movimentos as $mov) {
                    Estoque::remover($mov);
                }
                $transferencia->delete();
            });

            return redirect()->route('estoque.transferencias.index')->with('mensagem', 'Transferência excluída com sucesso.');
        } catch (\Exception $e) {
            return redirect()->route('estoque.transferencias.index')->with('mensagem_erro', 'Erro ao excluir transferência: '.$e->getMessage());
        }
    }

    /**
     * Códigos de barras com saldo de um medicamento na clínica (Ajax).
     */
    public function getCodigosBarras(Request $request)
    {
        $user = auth()->user();
        $clinicaId = $request->filled('clinica_id') ? $request->clinica_id : $user->clinica_id;

        $codigos = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $request->medicamento_id)
            ->whereNotNull('codigo_barras')
            ->where('codigo_barras', '<>', '')
            ->get();

        return response()->json(['codigos' => $codigos]);
    }

    /**
     * Lotes com saldo de um medicamento+código na clínica (Ajax).
     */
    public function getLotes(Request $request)
    {
        $user = auth()->user();
        $clinicaId = $request->filled('clinica_id') ? $request->clinica_id : $user->clinica_id;

        $query = EstoqueSaldo::where('clinica_id', $clinicaId)
            ->where('medicamento_id', $request->medicamento_id);

        if ($request->filled('codigo_barras')) {
            $query->where('codigo_barras', $request->codigo_barras);
        }

        $lotes = $query->get();

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
     * Data de vencimento do lote/código.
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

    /**
     * Etiquetas de código de barras dos itens transferidos (auto-print, 3 colunas).
     */
    public function etiquetas($id)
    {
        $transferencia = Transferencia::with(['movimentos' => fn ($q) => $q->where('tipo', 'Saida')->with('medicamento')])
            ->findOrFail($id);

        return view('estoque.transferencias.etiquetas', compact('transferencia'));
    }

    /**
     * Espelho (listagem de confirmação) da transferência, para impressão.
     */
    public function espelho($id)
    {
        $transferencia = Transferencia::with(['origem', 'destino', 'user', 'movimentos.medicamento'])
            ->findOrFail($id);

        $saidas = $transferencia->movimentos->where('tipo', 'Saida');

        return view('estoque.transferencias.espelho', compact('transferencia', 'saidas'));
    }
}
