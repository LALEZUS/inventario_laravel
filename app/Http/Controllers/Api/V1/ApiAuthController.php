<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class ApiAuthController extends Controller
{
    public function login(Request $request, AuditLogger $audit): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $key = strtolower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos de acceso. Espera '.RateLimiter::availableIn($key).' segundos.',
                'errors' => ['username' => ['Límite de intentos excedido.']],
            ], 429);
        }

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.',
                'errors' => ['username' => ['Las credenciales ingresadas son incorrectas.']],
            ], 401);
        }

        RateLimiter::clear($key);

        $deviceName = $request->input('device_name') ?: 'Flutter App';
        // Directive 6: Deliver ONLY plainTextToken generated at createToken() time
        $token = $user->createToken($deviceName)->plainTextToken;

        $audit->record('login_api', 'users', $user->id, null, ['username' => $user->username, 'device' => $deviceName], $user);

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
            ],
        ], 200);
    }

    public function logout(Request $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        $audit->record('logout_api', 'users', $user?->id, ['username' => $user?->username], null, $user);

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Perfil de usuario obtenido.',
            'data' => new UserResource($request->user()),
        ], 200);
    }
}
