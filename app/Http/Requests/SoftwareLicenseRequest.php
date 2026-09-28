<?php

namespace App\Http\Requests;

use App\Models\SoftwareLicense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SoftwareLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $license = $this->route('softwareLicense');
        return $license instanceof SoftwareLicense
            ? Gate::allows('update', $license)
            : Gate::allows('create', SoftwareLicense::class);
    }

    public function rules(): array
    {
        $currentStatus = $this->route('softwareLicense')?->status;
        $allowedStatuses = array_values(array_unique(array_filter([
            ...config('inventory.catalogs.license_statuses'),
            $currentStatus,
        ])));

        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['nullable', 'string', 'max:50'],
            'key_value' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in($allowedStatuses)],
            'vendor' => ['nullable', 'string', 'max:100'],
            'link' => ['nullable', 'url', 'max:2000'],
            'comments' => ['nullable', 'string'],
        ];
    }
}
