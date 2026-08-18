<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Exibe o formulário para solicitar o link de redefinição de senha.
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Envia o link de redefinição de senha para o e-mail informado.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $response = Password::broker()->sendResetLink($request->only('email'));

        return $response == Password::RESET_LINK_SENT
            ? back()->with('status', 'Enviamos o link de redefinição de senha para o seu e-mail.')
            : back()->withErrors(['email' => 'Não encontramos uma conta com este e-mail.']);
    }
}
