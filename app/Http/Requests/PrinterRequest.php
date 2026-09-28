<?php

namespace App\Http\Requests;

use App\Models\Printer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $printer = $this->route('printer');

        return $printer instanceof Printer
            ? Gate::allows('update', $printer)
            : Gate::allows('create', Printer::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_network' => ['nullable', 'boolean'],
            'ip_address' => ['nullable', 'ip'],
            'zone' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'assigned_to' => ['nullable', 'string', 'max:150'],
            'supply_type' => ['nullable', Rule::in(['Tinta', 'Tóner', 'Mixto', 'Otro'])],
            'ink_type' => ['nullable', 'string', 'max:100'],
            'linked_inks' => ['nullable', 'array'],
            'linked_inks.*' => ['integer', 'exists:inks,id'],
            'linked_toner' => ['nullable', 'array'],
            'linked_toner.*' => ['integer', 'exists:toner,id'],
            'status' => ['required', Rule::in(['Activo', 'Disponible', 'En servicio', 'Mantenimiento', 'Baja'])],
            'comments' => ['nullable', 'string'],
        ];
    }
}
