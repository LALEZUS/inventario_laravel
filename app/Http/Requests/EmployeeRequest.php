<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');
        return $employee instanceof Employee ? Gate::allows('update', $employee) : Gate::allows('create', Employee::class);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        return [
            'full_name' => ['required', 'string', 'max:150', Rule::unique('employees', 'full_name')->ignore($employee?->id)],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'email_corporate' => ['nullable', 'email:rfc', 'max:100'],
            'extension' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::in(['Activo', 'Inactivo'])],
            'comments' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['full_name' => 'nombre completo', 'email_corporate' => 'correo corporativo'];
    }
}
