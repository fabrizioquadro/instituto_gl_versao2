<?php

namespace App\Http\Controllers;

use App\Models\CodigoBarraMedicamento;
use App\Models\Entrada;
use App\Models\Estoque;
use App\Models\Fornecedor;
use App\Models\Medicamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntradaSistemaController extends Controller
{
    /**
     * Lista as entradas da clínica do usuário logado.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Entrada::with(['fornecedor', 'clinica', 'user'])
            ->withCount('movimentos')
            ->where('clinica_id', $user->clinica_id);

        if ($request->filled('medicamento_id')) {
            $query->whereHas('movimentos', fn ($q) => $q->where('medicamento_id', (int) $request->medicamento_id));
        }

        $entradas = $query->orderByDesc('data')->get();
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome']);

        return view('estoque.entradas.index', compact('entradas', 'medicamentos'));
    }

    /**
     * Formulário para adicionar entrada.
     */
    public function create()
    {
        $fornecedores = Fornecedor::orderBy('nome')->get();
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('estoque.entradas.create', compact('fornecedores', 'medicamentos'));
    }

    /**
     * Salva uma nova entrada com seus medicamentos (movimentos).
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'fornecedor_id' => 'required|integer|exists:fornecedores,id',
            'data' => 'required|date',
            'nota' => 'nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request, $user) {
                $entrada = Entrada::create([
                    'clinica_id' => $user->clinica_id,
                    'user_id' => $user->id,
                    'fornecedor_id' => $request->fornecedor_id,
                    'nota' => $request->nota,
                    'data' => $request->data,
                    'valor' => valorFormDb($request->total_entrada ?: '0,00'),
                ]);

                if ($request->hasFile('arquivo') && $request->file('arquivo')->isValid()) {
                    $extensao = $request->arquivo->extension();
                    $nome = $entrada->id.'.'.$extensao;
                    $request->arquivo->move(public_path('img/entradas/notas'), $nome);
                    $entrada->update(['arquivo' => $nome]);
                }

                $this->salvarLinhas($request, $entrada->id, $user->clinica_id);

                return redirect()->route('estoque.entradas.show', $entrada->id)
                    ->with('mensagem', 'Entrada cadastrada com sucesso.')
                    ->with('abrir_espelho', true);
            });
        } catch (\Exception $e) {
            return redirect()->route('estoque.entradas.index')->with('mensagem_erro', 'Erro ao cadastrar entrada: '.$e->getMessage());
        }
    }

    /**
     * Exibe uma entrada.
     */
    public function show($id)
    {
        $entrada = Entrada::with(['fornecedor', 'clinica', 'movimentos.medicamento'])->findOrFail($id);

        return view('estoque.entradas.show', compact('entrada'));
    }

    /**
     * Formulário para editar entrada.
     */
    public function edit($id)
    {
        $entrada = Entrada::with('movimentos')->findOrFail($id);
        $fornecedores = Fornecedor::orderBy('nome')->get();
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('estoque.entradas.edit', compact('entrada', 'fornecedores', 'medicamentos'));
    }

    /**
     * Atualiza uma entrada (estorna os movimentos antigos e regrava).
     */
    public function update(Request $request, $id)
    {
        $entrada = Entrada::findOrFail($id);

        $request->validate([
            'fornecedor_id' => 'required|integer|exists:fornecedores,id',
            'data' => 'required|date',
            'nota' => 'nullable|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request, $entrada) {
                $entrada->update([
                    'fornecedor_id' => $request->fornecedor_id,
                    'nota' => $request->nota,
                    'data' => $request->data,
                    'valor' => valorFormDb($request->total_entrada ?: '0,00'),
                ]);

                if ($request->hasFile('arquivo') && $request->file('arquivo')->isValid()) {
                    $extensao = $request->arquivo->extension();
                    $nome = $entrada->id.'.'.$extensao;
                    $request->arquivo->move(public_path('img/entradas/notas'), $nome);
                    $entrada->update(['arquivo' => $nome]);
                }

                // estorna os movimentos antigos e regrava
                foreach ($entrada->movimentos as $mov) {
                    Estoque::remover($mov);
                }

                $this->salvarLinhas($request, $entrada->id, $entrada->clinica_id);

                return redirect()->route('estoque.entradas.index')->with('mensagem', 'Entrada atualizada com sucesso.');
            });
        } catch (\Exception $e) {
            return redirect()->route('estoque.entradas.index')->with('mensagem_erro', 'Erro ao atualizar entrada: '.$e->getMessage());
        }
    }

    /**
     * Exclui uma entrada (estorna todos os movimentos).
     */
    public function destroy($id)
    {
        $entrada = Entrada::with('movimentos')->findOrFail($id);

        try {
            DB::transaction(function () use ($entrada) {
                foreach ($entrada->movimentos as $mov) {
                    Estoque::remover($mov);
                }
                $entrada->delete();
            });

            return redirect()->route('estoque.entradas.index')->with('mensagem', 'Entrada excluída com sucesso.');
        } catch (\Exception $e) {
            return redirect()->route('estoque.entradas.index')->with('mensagem_erro', 'Erro ao excluir entrada: '.$e->getMessage());
        }
    }

    /**
     * Gera um novo código de barras próprio para o medicamento (Ajax).
     */
    public function gerarCodigoBarras(Request $request)
    {
        $controle = CodigoBarraMedicamento::where('medicamento_id', $request->medicamento_id)->first();

        if (! $controle) {
            $codigo = 1;
            CodigoBarraMedicamento::create([
                'medicamento_id' => $request->medicamento_id,
                'contador' => $codigo,
            ]);
        } else {
            $codigo = $controle->contador + 1;
            $controle->update(['contador' => $codigo]);
        }

        $parteMed = str_pad((string) $request->medicamento_id, 2, '0', STR_PAD_LEFT);
        $parteCod = str_pad((string) $codigo, 5, '0', STR_PAD_LEFT);

        return response()->json(['codigo' => $parteMed.$parteCod]);
    }

    /**
     * Página de etiquetas de código de barras de uma entrada (auto-print).
     */
    public function etiquetas($id)
    {
        $entrada = Entrada::with('movimentos')->findOrFail($id);

        return view('estoque.entradas.etiquetas', compact('entrada'));
    }

    /**
     * Espelho (listagem de confirmação) da entrada, para impressão.
     */
    public function espelho($id)
    {
        $entrada = Entrada::with(['fornecedor', 'clinica', 'movimentos.medicamento'])->findOrFail($id);

        return view('estoque.entradas.espelho', compact('entrada'));
    }

    /**
     * Percorre as linhas dinâmicas da entrada e grava os movimentos.
     */
    private function salvarLinhas(Request $request, int $entradaId, int $clinicaId): void
    {
        $contador = (int) $request->contador_medicamentos;

        for ($i = 1; $i <= $contador; $i++) {
            $medicamentoId = $request->get("medicamento_id_{$i}");
            if (! $medicamentoId) {
                continue;
            }

            $quantidade = $request->get("quantidade_{$i}");
            $valor = $request->get("valor_{$i}", '0,00');
            $total = $request->get("total_{$i}", '0,00');
            $lote = strtoupper((string) $request->get("lote_{$i}"));
            $dtVencimento = $request->get("dt_vencimento_{$i}");
            $codigoBarras = $request->get("codigo_barras_{$i}");

            if (! $quantidade || ! $lote) {
                continue;
            }

            Estoque::registrar([
                'clinica_id' => $clinicaId,
                'entrada_id' => $entradaId,
                'medicamento_id' => $medicamentoId,
                'user_id' => auth()->id(),
                'origem' => 'Entrada',
                'tipo' => 'Entrada',
                'quantidade' => $quantidade,
                'valor' => valorFormDb($valor),
                'total' => valorFormDb($total),
                'lote' => $lote,
                'dt_vencimento' => $dtVencimento,
                'codigo_barras' => $codigoBarras,
            ]);

            $valorDb = valorFormDb($valor);
            if ((float) $valorDb > 0) {
                $medicamento = Medicamento::find($medicamentoId);
                if ($medicamento) {
                    $medicamento->update(['ultimo_valor_pg' => $valorDb]);
                }
            }
        }
    }
}
