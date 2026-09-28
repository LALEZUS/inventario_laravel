<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            ? Gate::allows('update', $user)
            : Gate::allows('create', User::class);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'full_name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(['admin', 'soporte', 'consulta'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'nombre completo',
            'username' => 'nombre de usuario',
            'password' => 'contraseña',
            'role' => 'rol',
        ];
    }

    public function messages(): array
    {
        return ['username.regex' => 'El usuario solo puede contener letras, numeros, punto, guion y guion bajo.'];
    }
}
