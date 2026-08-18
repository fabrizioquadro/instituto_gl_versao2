<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Estoque;
use App\Models\EstoqueSaldo;
use App\Models\Medicamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstoqueAjusteController extends Controller
{
    /**
     * Lista os ajustes de estoque.
     */
    public function index()
    {
        $ajustes = Estoque::with(['medicamento', 'clinica', 'user'])
            ->where('origem', 'Ajuste')
            ->orderByDesc('created_at')
            ->get();

        return view('config.ajustes.index', compact('ajustes'));
    }

    /**
     * Formulário para adicionar ajuste.
     */
    public function create()
    {
        $medicamentos = Medicamento::orderBy('nome')->get();
        $clinicas = Clinica::orderBy('nome')->get();

        return view('config.ajustes.create', compact('medicamentos', 'clinicas'));
    }

    /**
     * Salva um ajuste de estoque (movimento origem=Ajuste).
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'clinica_id' => 'required|integer|exists:clinicas,id',
            'medicamento_id' => 'required|integer|exists:medicamentos,id',
            'tipo' => 'required|in:Entrada,Saida',
            'quantidade' => 'required|numeric|min:0.01',
            'lote' => 'required|string|max:255',
            'motivo' => 'required|string|max:255',
        ]);

        $codigoBarras = $request->codigo_barras ?: null;

        try {
            // valida saldo quando é saída
            if ($request->tipo === 'Saida') {
                $disponivel = $this->saldoDisponivel($request->clinica_id, $request->medicamento_id, $request->lote, $codigoBarras);
                if ((float) $request->quantidade > $disponivel) {
                    return redirect()->back()->withInput()->with('mensagem_erro', 'Quantidade do ajuste excede o saldo disponível ('.number_format($disponivel, 2, ',', '.').').');
                }
            }

            Estoque::registrar([
                'clinica_id' => $request->clinica_id,
                'medicamento_id' => $request->medicamento_id,
                'user_id' => $user->id,
                'origem' => 'Ajuste',
                'tipo' => $request->tipo,
                'quantidade' => $request->quantidade,
                'valor' => 0,
                'total' => 0,
                'lote' => strtoupper($request->lote),
                'dt_vencimento' => $request->dt_vencimento ?: null,
                'codigo_barras' => $codigoBarras,
                'motivo' => $request->motivo,
            ]);

            return redirect()->route('estoque.ajustes.index')->with('mensagem', 'Ajuste de estoque registrado com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao registrar ajuste: '.$e->getMessage());
        }
    }

    /**
     * Exibe um ajuste.
     */
    public function show($id)
    {
        $ajuste = Estoque::with(['medicamento', 'clinica', 'user'])->findOrFail($id);

        return view('config.ajustes.show', compact('ajuste'));
    }

    /**
     * Exclui um ajuste (estorna o movimento).
     */
    public function destroy($id)
    {
        $ajuste = Estoque::findOrFail($id);

        try {
            Estoque::remover($ajuste);

            return redirect()->route('estoque.ajustes.index')->with('mensagem', 'Ajuste excluído (estornado) com sucesso.');
        } catch (\Exception $e) {
            return redirect()->route('estoque.ajustes.index')->with('mensagem_erro', 'Erro ao excluir ajuste: '.$e->getMessage());
        }
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
}
