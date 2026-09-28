<?php

namespace App\Http\Requests;

use App\Models\Peripheral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PeripheralRequest extends FormRequest
{
    public function authorize(): bool
    {
        $peripheral = $this->route('peripheral');
        return $peripheral instanceof Peripheral
            ? Gate::allows('update', $peripheral)
            : Gate::allows('create', Peripheral::class);
    }

    public function rules(): array
    {
        $peripheral = $this->route('peripheral');
        return [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('peripherals', 'code')->ignore($peripheral?->id)],
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['Disponible', 'Asignado', 'Nuevo', 'Usado', 'Mantenimiento', 'Baja'])],
            'location' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'comments' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'string', 'max:150'],
            'computer_id' => ['nullable', 'integer', 'exists:hardware_assets,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
