<?php

namespace App\Http\Requests;

use App\Models\EnterpriseNetwork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EnterpriseNetworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $network = $this->route('enterpriseNetwork');
        return $network instanceof EnterpriseNetwork ? Gate::allows('update', $network) : Gate::allows('create', EnterpriseNetwork::class);
    }

    public function rules(): array
    {
        return [
            'network_name' => ['required', 'string', 'max:100'],
            'vlan' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:150'],
            'password' => ['nullable', 'string', 'max:100'],
            'encryption' => ['nullable', 'string', 'max:50'],
            'comments' => ['nullable', 'string'],
            'notes_extra' => ['nullable', 'string'],
        ];
    }
}
