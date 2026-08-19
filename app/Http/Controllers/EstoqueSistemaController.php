<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\EstoqueAberto;
use App\Models\EstoqueSaldo;
use App\Models\Medicamento;
use Illuminate\Http\Request;

class EstoqueSistemaController extends Controller
{
    /**
     * Saldo de estoque da clínica do usuário logado, com alertas de
     * estoque médio (amarelo) e mínimo (vermelho) por medicamento.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Admin pode visualizar o estoque de qualquer clínica; demais usuários
        // ficam restritos à própria clínica.
        if ($user->isAdmin() && $request->filled('clinica_id')) {
            $clinicaId = (int) $request->clinica_id;
        } else {
            $clinicaId = $user->clinica_id;
        }
        $clinica = Clinica::find($clinicaId);

        $query = EstoqueSaldo::with('medicamento')->where('clinica_id', $clinicaId);

        if ($request->filled('medicamento_id')) {
            $query->where('medicamento_id', $request->medicamento_id);
        }

        if ($request->filled('alerta') && $request->alerta != 'todos') {
            // filtro de alerta aplicado em memória (depende do total por medicamento)
        }

        $saldos = $query->orderBy('medicamento_id')->orderBy('lote')->get();

        // Total por medicamento -> define o alerta (usando estoque_minimo/estoque_medio)
        $alertaPorMed = [];
        foreach ($saldos->groupBy('medicamento_id') as $medId => $linhas) {
            $med = $linhas->first()->medicamento;
            $total = (float) $linhas->sum('saldo');
            $alerta = 'ok';
            if ($med) {
                if ($med->estoque_minimo && $total < $med->estoque_minimo) {
                    $alerta = 'vermelho';
                } elseif ($med->estoque_medio && $total < $med->estoque_medio) {
                    $alerta = 'amarelo';
                }
            }
            $alertaPorMed[$medId] = $alerta;
        }

        // aplica filtro de alerta
        if ($request->filled('alerta') && $request->alerta != 'todos') {
            $filtro = $request->alerta;
            $saldos = $saldos->filter(function ($s) use ($alertaPorMed, $filtro) {
                return ($alertaPorMed[$s->medicamento_id] ?? 'ok') === $filtro;
            });
        }

        $medicamentos = Medicamento::orderBy('nome')->get();
        $clinicas = Clinica::orderBy('nome')->get();

        return view('estoque.estoques.index', compact('saldos', 'medicamentos', 'alertaPorMed', 'clinicas', 'clinicaId', 'clinica'));
    }

    /**
     * Estoques abertos (frascos abertos / EstoqueAberto) da clínica, com
     * filtros por clínica, medicamento e situação.
     */
    public function abertos(Request $request)
    {
        $user = auth()->user();

        if ($user->isAdmin() && $request->filled('clinica_id')) {
            $clinicaId = (int) $request->clinica_id;
        } else {
            $clinicaId = $user->clinica_id;
        }
        $clinica = Clinica::find($clinicaId);

        $query = EstoqueAberto::with('medicamento', 'user', 'clinica')
            ->where('clinica_id', $clinicaId);

        if ($request->filled('medicamento_id')) {
            $query->where('medicamento_id', (int) $request->medicamento_id);
        }

        if ($request->filled('situacao') && $request->situacao != 'todos') {
            $query->where('situacao', $request->situacao);
        }

        $abertos = $query->orderBy('dt_cadastro', 'desc')->get();

        $medicamentos = Medicamento::orderBy('nome')->get();
        $clinicas = Clinica::orderBy('nome')->get();

        return view('estoque.abertos.index', compact('abertos', 'medicamentos', 'clinicas', 'clinicaId', 'clinica'));
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
     * Lotes com saldo de um medicamento na clínica (Ajax).
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
}
