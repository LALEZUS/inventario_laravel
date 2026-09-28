<?php

namespace App\Http\Requests;

use App\Models\HardwareAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HardwareAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $computer = $this->route('computer');

        return $computer instanceof HardwareAsset
            ? Gate::allows('update', $computer)
            : Gate::allows('create', HardwareAsset::class);
    }

    public function rules(): array
    {
        $computer = $this->route('computer');

        return [
            'name' => ['nullable', 'string', 'max:100', 'required_without:nfo_file'],
            'category' => ['nullable', 'string', 'max:50'],
            'format' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('hardware_assets', 'code')->ignore($computer?->id)],
            'serial' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['DISPONIBLE', 'ENTREGADO', 'MANTENIMIENTO', 'BAJA'])],
            'location' => ['nullable', 'string', 'max:100'],
            'zone' => ['nullable', 'string', 'max:50'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'assigned_user' => ['nullable', 'string', 'max:150'],
            'delivery_date' => ['nullable', 'date'],
            'processor' => ['nullable', 'string', 'max:100'],
            'ram' => ['nullable', 'string', 'max:20'],
            'storage' => ['nullable', 'string', 'max:50'],
            'os' => ['nullable', 'string', 'max:100'],
            'os_version' => ['nullable', 'string', 'max:120'],
            'architecture' => ['nullable', 'string', 'max:100'],
            'bios' => ['nullable', 'string', 'max:255'],
            'motherboard' => ['nullable', 'string', 'max:150'],
            'gpu' => ['nullable', 'string', 'max:150'],
            'network_adapter' => ['nullable', 'string', 'max:150'],
            'mac_address' => ['nullable', 'string', 'max:80'],
            'secure_boot' => ['nullable', 'string', 'max:80'],
            'tpm' => ['nullable', 'string', 'max:120'],
            'admin_password' => ['nullable', 'string', 'max:255'],
            'anydesk_id' => ['nullable', 'string', 'max:50'],
            'rustdesk_id' => ['nullable', 'string', 'max:100'],
            'value' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'has_office' => ['nullable', 'boolean'],
            'has_winrar' => ['nullable', 'boolean'],
            'has_reader' => ['nullable', 'boolean'],
            'has_server' => ['nullable', 'boolean'],
            'has_printer' => ['nullable', 'boolean'],
            'comments' => ['nullable', 'string'],
            'nfo_file' => [
                'nullable',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strtolower($value->getClientOriginalExtension()) !== 'nfo') {
                        $fail('El archivo debe tener extension .nfo.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del equipo',
            'code' => 'folio',
            'employee_id' => 'empleado',
            'nfo_file' => 'archivo NFO',
            'anydesk_id' => 'ID de AnyDesk',
            'rustdesk_id' => 'ID de RustDesk',
            'value' => 'valor',
        ];
    }
}
