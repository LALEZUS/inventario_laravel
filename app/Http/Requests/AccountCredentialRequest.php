<?php

namespace App\Http\Requests;

use App\Models\AccountCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AccountCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credential = $this->route('accountCredential');
        return $credential instanceof AccountCredential
            ? Gate::allows('update', $credential)
            : Gate::allows('create', AccountCredential::class);
    }

    public function rules(): array
    {
        $id = $this->route('accountCredential')?->id;
        $currentStatus = $this->route('accountCredential')?->status;
        $allowedStatuses = array_values(array_unique(array_filter([
            ...config('inventory.catalogs.account_statuses'),
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
