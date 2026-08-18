<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller
{
    /**
     * Exibe a página de perfil do usuário logado.
     */
    public function index()
    {
        return view('perfil.index', [
            'clinicas' => Clinica::orderBy('nome')->get(),
        ]);
    }

    /**
     * Atualiza os dados do perfil (nome, e-mail, clínica e foto).
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ];

        $request->validate($rules);

        $user->nome = $request->nome;
        $user->email = $request->email;

        // Clínica (escala) — qualquer usuário pode definir a clínica ativa
        if ($request->has('clinica_id')) {
            $user->clinica_id = $request->filled('clinica_id') ? $request->clinica_id : null;
        }

        if ($request->hasFile('imagem')) {
            $arquivo = $request->file('imagem');
            $nomeArquivo = $user->id . '.' . $arquivo->extension();
            $arquivo->move(public_path('img/usuarios'), $nomeArquivo);
            $user->imagem = $nomeArquivo;
        }

        $user->save();

        return back()->with('sucesso', 'Perfil atualizado com sucesso.');
    }

    /**
     * Exibe a página de alteração de senha.
     */
    public function alterarSenha()
    {
        return view('alterar_senha.index');
    }

    /**
     * Altera a senha do usuário logado.
     */
    public function updateSenha(Request $request)
    {
        $request->validate([
            'senha_atual' => 'required|current_password',
            'password' => 'required|confirmed|min:8',
        ]);

        auth()->user()->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return back()->with('sucesso', 'Senha alterada com sucesso.');
    }
}
