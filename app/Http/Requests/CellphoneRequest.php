<?php

namespace App\Http\Requests;

use App\Models\Cellphone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CellphoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cellphone = $this->route('cellphone');
        return $cellphone instanceof Cellphone
            ? Gate::allows('update', $cellphone)
            : Gate::allows('create', Cellphone::class);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'employee_name_legacy' => ['nullable', 'string', 'max:150'],
            'model' => ['required', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'email_account' => ['nullable', 'string', 'max:100'],
            'recovery_account' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'max:100'],
            'updated_password' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'string', 'max:50'],
            'app_lock_password' => ['nullable', 'string', 'max:100'],
            'app_lock_answer' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['En Uso', 'Disponible', 'Mantenimiento', 'Baja'])],
            'update_note' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
            'has_app_lock' => ['nullable', 'boolean'],
            'app_lock_pattern' => ['nullable', 'string', 'max:100'],
        ];
    }
}
