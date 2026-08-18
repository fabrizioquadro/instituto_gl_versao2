<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use Illuminate\Http\Request;

class GrupoAdmController extends Controller
{
    /**
     * Lista os grupos.
     */
    public function index()
    {
        $grupos = Grupo::withCount('medicamentos')->orderBy('nome')->get();

        return view('config.grupos.index', compact('grupos'));
    }

    /**
     * Formulário para adicionar grupo.
     */
    public function create()
    {
        return view('config.grupos.create');
    }

    /**
     * Salva um novo grupo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        Grupo::create($request->only('nome'));

        return redirect()->route('config.grupos.index')->with('mensagem', 'Grupo cadastrado com sucesso.');
    }

    /**
     * Exibe um grupo.
     */
    public function show($id)
    {
        $grupo = Grupo::with('medicamentos')->findOrFail($id);

        return view('config.grupos.show', compact('grupo'));
    }

    /**
     * Formulário para editar grupo.
     */
    public function edit($id)
    {
        $grupo = Grupo::findOrFail($id);

        return view('config.grupos.edit', compact('grupo'));
    }

    /**
     * Atualiza um grupo.
     */
    public function update(Request $request, $id)
    {
        $grupo = Grupo::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
        ]);

        $grupo->update($request->only('nome'));

        return redirect()->route('config.grupos.index')->with('mensagem', 'Grupo atualizado com sucesso.');
    }

    /**
     * Exclui um grupo.
     */
    public function destroy($id)
    {
        $grupo = Grupo::findOrFail($id);
        $grupo->delete();

        return redirect()->route('config.grupos.index')->with('mensagem', 'Grupo excluído com sucesso.');
    }
}
