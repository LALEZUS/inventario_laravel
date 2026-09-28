<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutlookAccountApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('correos_outlook')?->id;

        return [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'correo' => ['required', 'email:rfc', 'max:150', Rule::unique('correos_outlook', 'correo')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', 'string', 'max:255'],
            'contraseña' => ['nullable', 'string', 'max:255'],
            'estatus' => ['required', Rule::in(['ACTIVA', 'BAJA'])],
            'comentarios' => ['nullable', 'string'],
            'servidor_entrada' => ['nullable', 'string', 'max:255'],
            'puerto_entrada' => ['nullable', 'string', 'max:20'],
            'ssl_entrada' => ['nullable', 'boolean'],
            'servidor_salida' => ['nullable', 'string', 'max:255'],
            'puerto_salida' => ['nullable', 'string', 'max:20'],
            'cifrado_salida' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $legacyPassword = null;
        foreach ($this->all() as $key => $value) {
            if (is_string($key) && str_contains($key, 'contrase') && str_ends_with($key, 'a')) {
                $legacyPassword = $value;
                break;
            }
        }

        $data = ['ssl_entrada' => $this->boolean('ssl_entrada')];
        if (!$this->has('password') && !$this->has('contraseña') && $legacyPassword !== null) {
            $data['contraseña'] = $legacyPassword;
        }

        $this->merge($data);
    }

    public function messages(): array
    {
        return [
            'correo.unique' => 'Este correo ya esta registrado en Outlook.',
            'correo.required' => 'El correo Outlook es obligatorio.',
            'correo.email' => 'Escribe un correo valido.',
            'password.required' => 'La contraseña del correo es obligatoria.',
            'contraseña.required' => 'La contraseña del correo es obligatoria.',
            'estatus.required' => 'El estatus es obligatorio.',
            'estatus.in' => 'El estatus debe ser ACTIVA o BAJA.',
        ];
    }
}
