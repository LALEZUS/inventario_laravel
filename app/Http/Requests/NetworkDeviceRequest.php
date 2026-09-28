<?php

namespace App\Http\Requests;

use App\Models\NetworkDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class NetworkDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $device = $this->route('networkDevice');
        return $device instanceof NetworkDevice ? Gate::allows('update', $device) : Gate::allows('create', NetworkDevice::class);
    }

    public function rules(): array
    {
        return [
            'device_name' => ['required', 'string', 'max:100'],
            'device_type' => ['nullable', 'string', 'max:50'],
            'ip_address' => ['nullable', 'ip'],
            'mac_address' => ['nullable', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'location' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['Activo', 'Inactivo', 'Mantenimiento', 'Desconocido'])],
            'comments' => ['nullable', 'string'],
        ];
    }
}
