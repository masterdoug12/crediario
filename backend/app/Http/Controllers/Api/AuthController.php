<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Realiza login do administrador e retorna token de acesso.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'mensagem' => 'Credenciais inválidas.',
            ], 422);
        }

        $tokenName = $credentials['device_name'] ?? 'frontend';
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Revoga o token atual do usuário autenticado.
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'mensagem' => 'Logout realizado com sucesso.',
        ]);
    }
}
