<?php

namespace App\Http\Requests;

use App\Models\Ink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ink = $this->route('ink');

        return $ink instanceof Ink ? Gate::allows('update', $ink) : Gate::allows('create', Ink::class);
    }

    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'purchase_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'status' => ['required', Rule::in(['Disponible', 'Completo', 'Bajo', 'Agotado', 'Vencido'])],
            'comments' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['low_stock_threshold' => 2]);
    }
}
