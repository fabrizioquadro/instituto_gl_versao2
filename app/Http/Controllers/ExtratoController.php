<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Estoque;
use App\Models\Medicamento;
use Illuminate\Http\Request;

class ExtratoController extends Controller
{
    /**
     * Formulário do extrato por medicamento (e opcionalmente código de barras).
     */
    public function index()
    {
        $medicamentos = Medicamento::orderBy('nome')->get();
        $clinicas = Clinica::orderBy('nome')->get();

        return view('estoque.extrato.index', compact('medicamentos', 'clinicas'));
    }

    /**
     * Códigos de barras distintos de um medicamento (Ajax).
     */
    public function codigos(Request $request)
    {
        $codigos = Estoque::where('medicamento_id', $request->medicamento_id)
            ->whereNotNull('codigo_barras')
            ->where('codigo_barras', '<>', '')
            ->distinct()
            ->orderBy('codigo_barras')
            ->pluck('codigo_barras');

        return response()->json(['codigos' => $codigos]);
    }

    /**
     * Gera o extrato de movimentações do medicamento (com saldo acumulado).
     */
    public function gerar(Request $request)
    {
        $request->validate([
            'medicamento_id' => 'required|integer|exists:medicamentos,id',
        ]);

        $medicamento = Medicamento::with('grupo')->findOrFail($request->medicamento_id);
        $clinicas = Clinica::orderBy('nome')->get();

        $query = Estoque::with(['clinica', 'medicamento'])
            ->where('medicamento_id', $medicamento->id);

        if ($request->filled('codigo_barras')) {
            $query->where('codigo_barras', $request->codigo_barras);
        }
        if ($request->filled('clinica_id')) {
            $query->where('clinica_id', $request->clinica_id);
        }

        $movimentos = $query->orderBy('created_at')->orderBy('id')->get();

        // saldo acumulado (global e por código)
        $saldoAcumulado = 0;
        $saldoPorCodigo = [];
        foreach ($movimentos as $m) {
            $delta = (float) $m->quantidade * ($m->tipo === 'Saida' ? -1 : 1);
            $saldoAcumulado += $delta;

            $chave = $m->codigo_barras ?: ('lote:' . $m->lote);
            $saldoPorCodigo[$chave] = ($saldoPorCodigo[$chave] ?? 0) + $delta;

            $m->saldo_acumulado = $saldoAcumulado;
            $m->saldo_codigo = $saldoPorCodigo[$chave];
        }

        return view('estoque.extrato.gerar', compact('medicamento', 'movimentos', 'clinicas', 'saldoAcumulado', 'request'));
    }
}
