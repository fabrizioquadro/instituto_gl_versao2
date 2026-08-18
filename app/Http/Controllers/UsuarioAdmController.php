<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioAdmController extends Controller
{
    /**
     * Permissões por módulo (booleans na tabela users da V2).
     */
    private $opcoes = ['controle_medicamentos', 'pacientes', 'procedimentos', 'financeiro'];

    /**
     * Lista os usuários.
     */
    public function index()
    {
        $usuarios = User::with('clinica')->orderBy('nome')->get();
        return view('config.usuarios.index', compact('usuarios'));
    }

    /**
     * Formulário para adicionar usuário.
     */
    public function create()
    {
        $clinicas = Clinica::orderBy('nome')->get();
        $opcoes = $this->opcoes;
        return view('config.usuarios.create', compact('clinicas', 'opcoes'));
    }

    /**
     * Salva um novo usuário.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,secretaria,enfermagem',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ]);

        $dados = $request->only('nome', 'email', 'role', 'clinica_id', 'coren', 'senha_certificado');
        $dados['password'] = Hash::make($request->password);
        $dados['ativo'] = $request->boolean('ativo');

        foreach ($this->opcoes as $opcao) {
            $dados[$opcao] = $request->boolean($opcao);
        }

        $user = User::create($dados);

        $this->processarImagens($request, $user);

        return redirect()->route('config.usuarios.index')->with('mensagem', 'Usuário cadastrado com sucesso.');
    }

    /**
     * Exibe um usuário.
     */
    public function show($id)
    {
        $user = User::with('clinica')->findOrFail($id);
        $opcoes = $this->opcoes;
        return view('config.usuarios.show', compact('user', 'opcoes'));
    }

    /**
     * Formulário para editar usuário.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $clinicas = Clinica::orderBy('nome')->get();
        $opcoes = $this->opcoes;
        return view('config.usuarios.edit', compact('user', 'clinicas', 'opcoes'));
    }

    /**
     * Atualiza um usuário.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'role' => 'required|in:admin,secretaria,enfermagem',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ]);

        $dados = $request->only('nome', 'email', 'role', 'clinica_id', 'coren', 'senha_certificado');
        $dados['ativo'] = $request->boolean('ativo');

        if ($request->filled('password')) {
            $dados['password'] = Hash::make($request->password);
        }

        foreach ($this->opcoes as $opcao) {
            $dados[$opcao] = $request->boolean($opcao);
        }

        $user->update($dados);

        $this->processarImagens($request, $user);

        return redirect()->route('config.usuarios.index')->with('mensagem', 'Usuário atualizado com sucesso.');
    }

    /**
     * Exclui um usuário.
     */
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return redirect()->route('config.usuarios.index')->with('mensagem', 'Usuário excluído com sucesso.');
    }

    /**
     * Move as imagens de perfil e carimbo/certificado, se enviadas.
     */
    private function processarImagens(Request $request, User $user): void
    {
        if ($request->hasFile('imagem') && $request->file('imagem')->isValid()) {
            $ext = $request->file('imagem')->extension();
            $nmImagem = $user->id . '.' . $ext;
            $request->file('imagem')->move(public_path('img/usuarios'), $nmImagem);
            $user->imagem = $nmImagem;
            $user->save();
        }

        if ($request->hasFile('imagem_carimbo') && $request->file('imagem_carimbo')->isValid()) {
            $arquivo = $request->file('imagem_carimbo');
            $nmCarimbo = $user->id . '_' . $arquivo->getClientOriginalName();
            $arquivo->move(public_path('img/usuarios/certificados_digitais'), $nmCarimbo);
            $user->imagem_carimbo = $nmCarimbo;
            $user->save();
        }
    }
}
