<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;

class FornecedorAdmController extends Controller
{
    /**
     * Lista os fornecedores.
     */
    public function index()
    {
        $fornecedores = Fornecedor::orderBy('nome')->get();

        return view('config.fornecedores.index', compact('fornecedores'));
    }

    /**
     * Formulário para adicionar fornecedor.
     */
    public function create()
    {
        return view('config.fornecedores.create');
    }

    /**
     * Salva um novo fornecedor.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'tel' => 'nullable|string|max:20',
            'cel' => 'nullable|string|max:20',
            'situacao' => 'nullable|in:Ativo,Inativo',
        ]);

        $dados = $request->only('nome', 'cnpj', 'email', 'tel', 'cel');
        $dados['situacao'] = $request->situacao ?? 'Ativo';

        Fornecedor::create($dados);

        return redirect()->route('config.fornecedores.index')->with('mensagem', 'Fornecedor cadastrado com sucesso.');
    }

    /**
     * Exibe um fornecedor.
     */
    public function show($id)
    {
        $fornecedor = Fornecedor::findOrFail($id);

        return view('config.fornecedores.show', compact('fornecedor'));
    }

    /**
     * Formulário para editar fornecedor.
     */
    public function edit($id)
    {
        $fornecedor = Fornecedor::findOrFail($id);

        return view('config.fornecedores.edit', compact('fornecedor'));
    }

    /**
     * Atualiza um fornecedor.
     */
    public function update(Request $request, $id)
    {
        $fornecedor = Fornecedor::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'tel' => 'nullable|string|max:20',
            'cel' => 'nullable|string|max:20',
            'situacao' => 'nullable|in:Ativo,Inativo',
        ]);

        $dados = $request->only('nome', 'cnpj', 'email', 'tel', 'cel');
        $dados['situacao'] = $request->situacao ?? 'Ativo';

        $fornecedor->update($dados);

        return redirect()->route('config.fornecedores.index')->with('mensagem', 'Fornecedor atualizado com sucesso.');
    }

    /**
     * Exclui um fornecedor.
     */
    public function destroy($id)
    {
        $fornecedor = Fornecedor::findOrFail($id);
        $fornecedor->delete();

        return redirect()->route('config.fornecedores.index')->with('mensagem', 'Fornecedor excluído com sucesso.');
    }
}
