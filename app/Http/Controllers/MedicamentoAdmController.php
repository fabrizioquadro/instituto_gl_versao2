<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\Medicamento;
use Illuminate\Http\Request;

class MedicamentoAdmController extends Controller
{
    /**
     * Lista os medicamentos.
     */
    public function index()
    {
        $medicamentos = Medicamento::with('grupo')->orderBy('nome')->get();

        return view('config.medicamentos.index', compact('medicamentos'));
    }

    /**
     * Formulário para adicionar medicamento.
     */
    public function create()
    {
        $grupos = Grupo::orderBy('nome')->get();

        return view('config.medicamentos.create', compact('grupos'));
    }

    /**
     * Salva um novo medicamento.
     */
    public function store(Request $request)
    {
        $dados = $this->validar($request);

        $dados['ultimo_valor_pg'] = valorFormDb($request->ultimo_valor_pg ?? '0');
        $dados['vl_venda'] = valorFormDb($request->vl_venda ?? '0');
        $dados['estoque_minimo'] = $request->estoque_minimo ?? 0;
        $dados['estoque_medio'] = $request->estoque_medio ?? 0;

        Medicamento::create($dados);

        return redirect()->route('config.medicamentos.index')->with('mensagem', 'Medicamento cadastrado com sucesso.');
    }

    /**
     * Exibe um medicamento.
     */
    public function show($id)
    {
        $medicamento = Medicamento::with('grupo')->findOrFail($id);

        return view('config.medicamentos.show', compact('medicamento'));
    }

    /**
     * Formulário para editar medicamento.
     */
    public function edit($id)
    {
        $medicamento = Medicamento::with('grupo')->findOrFail($id);
        $grupos = Grupo::orderBy('nome')->get();

        return view('config.medicamentos.edit', compact('medicamento', 'grupos'));
    }

    /**
     * Atualiza um medicamento.
     */
    public function update(Request $request, $id)
    {
        $medicamento = Medicamento::findOrFail($id);

        $dados = $this->validar($request);

        $dados['ultimo_valor_pg'] = valorFormDb($request->ultimo_valor_pg ?? '0');
        $dados['vl_venda'] = valorFormDb($request->vl_venda ?? '0');
        $dados['estoque_minimo'] = $request->estoque_minimo ?? 0;
        $dados['estoque_medio'] = $request->estoque_medio ?? 0;

        $medicamento->update($dados);

        return redirect()->route('config.medicamentos.index')->with('mensagem', 'Medicamento atualizado com sucesso.');
    }

    /**
     * Exclui um medicamento.
     */
    public function destroy($id)
    {
        $medicamento = Medicamento::findOrFail($id);
        $medicamento->delete();

        return redirect()->route('config.medicamentos.index')->with('mensagem', 'Medicamento excluído com sucesso.');
    }

    /**
     * Valida e devolve apenas os campos permitidos do medicamento.
     */
    private function validar(Request $request): array
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'fabricante' => 'required|string|max:255',
            'tipo' => 'required|string|in:Ampola,Vasilhame,Procedimento',
            'vasilhame' => 'nullable|integer|required_if:tipo,Vasilhame',
            'ultimo_valor_pg' => 'nullable',
            'vl_venda' => 'required',
            'estoque_minimo' => 'nullable|numeric',
            'estoque_medio' => 'nullable|numeric',
            'situacao' => 'nullable|string|in:Ativo,Inativo',
            'aplicacao' => 'nullable|string|in:Sim,Não',
            'aplicacao_feegow_id' => 'nullable|integer',
            'grupo_id' => 'nullable|integer|exists:grupos,id',
        ]);

        $dados = $request->only([
            'nome', 'fabricante', 'tipo', 'vasilhame', 'situacao', 'aplicacao',
            'aplicacao_feegow_id', 'grupo_id',
        ]);

        $dados['situacao'] = $dados['situacao'] ?? 'Ativo';
        $dados['aplicacao'] = $dados['aplicacao'] ?? 'Não';

        // vasilhame só faz sentido quando tipo = Vasilhame
        if (($dados['tipo'] ?? null) !== 'Vasilhame') {
            $dados['vasilhame'] = null;
        }

        return $dados;
    }
}
