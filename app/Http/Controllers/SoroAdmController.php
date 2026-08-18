<?php

namespace App\Http\Controllers;

use App\Models\Medicamento;
use App\Models\Soro;
use App\Models\SoroMedicamento;
use Illuminate\Http\Request;

class SoroAdmController extends Controller
{
    /**
     * Lista os soros.
     */
    public function index()
    {
        $soros = Soro::withCount('medicamentos')->orderBy('nome')->get();

        return view('config.soros.index', compact('soros'));
    }

    /**
     * Formulário para adicionar soro.
     */
    public function create()
    {
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('config.soros.create', compact('medicamentos'));
    }

    /**
     * Salva um novo soro com seus medicamentos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $soro = Soro::create(['nome' => $request->nome]);
        $this->salvarLinhas($request, $soro->id);

        return redirect()->route('config.soros.index')->with('mensagem', 'Soro cadastrado com sucesso.');
    }

    /**
     * Exibe um soro.
     */
    public function show($id)
    {
        $soro = Soro::with('medicamentos.medicamento')->findOrFail($id);

        return view('config.soros.show', compact('soro'));
    }

    /**
     * Formulário para editar soro.
     */
    public function edit($id)
    {
        $soro = Soro::with('medicamentos.medicamento')->findOrFail($id);
        $medicamentos = Medicamento::orderBy('nome')->get();

        return view('config.soros.edit', compact('soro', 'medicamentos'));
    }

    /**
     * Atualiza um soro (nome + novas linhas de medicamentos).
     */
    public function update(Request $request, $id)
    {
        $soro = Soro::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $soro->update(['nome' => $request->nome]);
        $this->salvarLinhas($request, $soro->id);

        return redirect()->route('config.soros.index')->with('mensagem', 'Soro atualizado com sucesso.');
    }

    /**
     * Exclui um soro e seus medicamentos.
     */
    public function destroy($id)
    {
        $soro = Soro::findOrFail($id);
        SoroMedicamento::where('soro_id', $soro->id)->delete();
        $soro->delete();

        return redirect()->route('config.soros.index')->with('mensagem', 'Soro excluído com sucesso.');
    }

    /**
     * Retorna os medicamentos de um soro em JSON (usado na edição).
     */
    public function buscar($id)
    {
        $soro = Soro::with('medicamentos.medicamento')->findOrFail($id);

        $medicamentos = $soro->medicamentos->map(function ($med) {
            return [
                'soro_medicamento_id' => $med->id,
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
     * Exclui uma linha soro_medicamento (via Ajax, na edição).
     */
    public function deleteMedicamento($id)
    {
        SoroMedicamento::find($id)?->delete();

        return response()->json(['controle' => 'true']);
    }

    /**
     * Percorre as linhas dinâmicas (medicamento_id_N, quantidade_N, valor_N)
     * e grava as que estiverem preenchidas.
     */
    private function salvarLinhas(Request $request, int $soroId): void
    {
        $contador = (int) $request->contador;

        for ($i = 1; $i <= $contador; $i++) {
            $medicamento_id = $request->get("medicamento_id_{$i}");
            $quantidade = $request->get("quantidade_{$i}");
            $valor = $request->get("valor_{$i}");

            if ($medicamento_id !== null && $quantidade !== null && $valor !== null) {
                SoroMedicamento::create([
                    'soro_id' => $soroId,
                    'medicamento_id' => $medicamento_id,
                    'quantidade' => $quantidade,
                    'valor_unitario' => valorFormDb($valor),
                ]);
            }
        }
    }
}
