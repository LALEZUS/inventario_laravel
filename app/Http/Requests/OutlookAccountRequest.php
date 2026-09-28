<?php

namespace App\Http\Requests;

use App\Models\OutlookAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OutlookAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $account = $this->route('outlookAccount');
        return $account instanceof OutlookAccount
            ? Gate::allows('update', $account)
            : Gate::allows('create', OutlookAccount::class);
    }

    public function rules(): array
    {
        $id = $this->route('outlookAccount')?->id;

        return [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'correo' => ['required', 'email:rfc', 'max:150', Rule::unique('correos_outlook', 'correo')->ignore($id)],
            'contraseña' => [$id ? 'nullable' : 'required', 'string', 'max:255'],
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
        if (!$this->has('contraseña') && $legacyPassword !== null) {
            $data['contraseña'] = $legacyPassword;
        }

        $this->merge($data);
    }

    public function messages(): array
    {
        return [
            'correo.unique' => 'Este correo ya esta registrado en Outlook. Usa otro correo o edita el registro existente.',
            'correo.required' => 'Escribe el correo Outlook.',
            'correo.email' => 'Escribe un correo valido.',
            'contraseña.required' => 'Escribe la contraseña del correo.',
        ];
    }
}
