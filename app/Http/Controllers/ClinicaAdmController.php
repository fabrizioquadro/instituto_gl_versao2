<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use Illuminate\Http\Request;

class ClinicaAdmController extends Controller
{
    /**
     * Lista as clínicas.
     */
    public function index()
    {
        $clinicas = Clinica::orderBy('nome')->get();
        return view('config.clinicas.index', compact('clinicas'));
    }

    /**
     * Formulário para adicionar clínica.
     */
    public function create()
    {
        return view('config.clinicas.create');
    }

    /**
     * Salva uma nova clínica.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:20',
            'id_unidade_feegow' => 'required|integer',
        ]);

        Clinica::create($request->only('nome', 'cnpj', 'id_unidade_feegow'));

        return redirect()->route('config.clinicas.index')->with('mensagem', 'Clínica cadastrada com sucesso.');
    }

    /**
     * Exibe uma clínica.
     */
    public function show($id)
    {
        $clinica = Clinica::findOrFail($id);
        return view('config.clinicas.show', compact('clinica'));
    }

    /**
     * Formulário para editar clínica.
     */
    public function edit($id)
    {
        $clinica = Clinica::findOrFail($id);
        return view('config.clinicas.edit', compact('clinica'));
    }

    /**
     * Atualiza uma clínica.
     */
    public function update(Request $request, $id)
    {
        $clinica = Clinica::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:20',
            'id_unidade_feegow' => 'required|integer',
        ]);

        $clinica->update($request->only('nome', 'cnpj', 'id_unidade_feegow'));

        return redirect()->route('config.clinicas.index')->with('mensagem', 'Clínica atualizada com sucesso.');
    }

    /**
     * Exclui uma clínica.
     */
    public function destroy($id)
    {
        $clinica = Clinica::findOrFail($id);
        $clinica->delete();

        return redirect()->route('config.clinicas.index')->with('mensagem', 'Clínica excluída com sucesso.');
    }
}
