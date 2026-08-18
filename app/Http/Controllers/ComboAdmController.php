<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use App\Models\ComboMedicamento;
use App\Models\Medicamento;
use Illuminate\Http\Request;

class ComboAdmController extends Controller
{
    /**
     * Lista os combos.
     */
    public function index()
    {
        $combos = Combo::withCount('medicamentos')->orderBy('nome')->get();

        return view('config.combos.index', compact('combos'));
    }

    /**
     * Formulário para adicionar combo.
     */
    public function create()
    {
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('config.combos.create', compact('medicamentos'));
    }

    /**
     * Salva um novo combo com seus medicamentos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $combo = Combo::create(['nome' => $request->nome]);
        $this->salvarLinhas($request, $combo->id);

        return redirect()->route('config.combos.index')->with('mensagem', 'Combo cadastrado com sucesso.');
    }

    /**
     * Exibe um combo.
     */
    public function show($id)
    {
        $combo = Combo::with('medicamentos.medicamento')->findOrFail($id);

        return view('config.combos.show', compact('combo'));
    }

    /**
     * Formulário para editar combo.
     */
    public function edit($id)
    {
        $combo = Combo::with('medicamentos.medicamento')->findOrFail($id);
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('config.combos.edit', compact('combo', 'medicamentos'));
    }

    /**
     * Atualiza um combo (nome + novas linhas de medicamentos).
     */
    public function update(Request $request, $id)
    {
        $combo = Combo::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $combo->update(['nome' => $request->nome]);
        $this->salvarLinhas($request, $combo->id);

        return redirect()->route('config.combos.index')->with('mensagem', 'Combo atualizado com sucesso.');
    }

    /**
     * Exclui um combo e seus medicamentos.
     */
    public function destroy($id)
    {
        $combo = Combo::findOrFail($id);
        ComboMedicamento::where('combo_id', $combo->id)->delete();
        $combo->delete();

        return redirect()->route('config.combos.index')->with('mensagem', 'Combo excluído com sucesso.');
    }

    /**
     * Retorna os medicamentos de um combo em JSON (usado na edição).
     */
    public function buscar($id)
    {
        $combo = Combo::with('medicamentos.medicamento')->findOrFail($id);

        $medicamentos = $combo->medicamentos->map(function ($med) {
            return [
                'combo_medicamento_id' => $med->id,
                'medicamento_id' => $med->medicamento_id,
                'medicamento_nome' => $med->medicamento ? $med->medicamento->nome : null,
                'quantidade' => $med->quantidade,
                'valor' => valorDbForm($med->valor_unitario),
                'total' => valorDbForm($med->valor_unitario * $med->quantidade),
            ];
        });

        return response()->json(['medicamentos' => $medicamentos]);
    }

    /**
     * Exclui uma linha combo_medicamento (via Ajax, na edição).
     */
    public function deleteMedicamento($id)
    {
        ComboMedicamento::find($id)?->delete();

        return response()->json(['controle' => 'true']);
    }

    /**
     * Percorre as linhas dinâmicas (medicamento_id_N, quantidade_N, valor_N)
     * e grava as que estiverem preenchidas.
     */
    private function salvarLinhas(Request $request, int $comboId): void
    {
        $contador = (int) $request->contador;

        for ($i = 1; $i <= $contador; $i++) {
            $medicamento_id = $request->get("medicamento_id_{$i}");
            $quantidade = $request->get("quantidade_{$i}");
            $valor = $request->get("valor_{$i}");

            if ($medicamento_id !== null && $quantidade !== null && $valor !== null) {
                ComboMedicamento::create([
                    'combo_id' => $comboId,
                    'medicamento_id' => $medicamento_id,
                    'quantidade' => $quantidade,
                    'valor_unitario' => valorFormDb($valor),
                ]);
            }
        }
    }
}
