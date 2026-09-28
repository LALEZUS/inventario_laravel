<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountCredentialApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('accountCredential')?->id;
        $currentStatus = $this->route('accountCredential')?->status;
        $catalogStatuses = config('inventory.catalogs.account_statuses') ?? ['Activo', 'Baja', 'Inactivo', 'Suspendido'];
        $allowedStatuses = array_values(array_unique(array_filter([
            ...$catalogStatuses,
            $currentStatus,
        ])));

        return [
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('account_management', 'email')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(['Microsoft 365', 'Gmail', 'Hospedaje', 'Personal'])],
            'assigned_to' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'status' => ['required', Rule::in($allowedStatuses)],
            'comments' => ['nullable', 'string'],
        ];
    }
}