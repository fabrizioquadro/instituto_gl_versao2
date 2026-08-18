<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /**
     * Para onde redirecionar após redefinir a senha.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Exibe o formulário de redefinição de senha.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Redefine a senha do usuário.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $response = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $response == Password::PASSWORD_RESET
            ? redirect($this->redirectTo)->with('status', 'Sua senha foi redefinida com sucesso!')
            : back()->withErrors(['email' => 'Não foi possível redefinir a senha. Token inválido ou expirado.']);
    }
}
