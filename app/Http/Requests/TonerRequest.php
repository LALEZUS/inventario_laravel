<?php

namespace App\Http\Requests;

use App\Models\Toner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TonerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $toner = $this->route('toner');

        return $toner instanceof Toner ? Gate::allows('update', $toner) : Gate::allows('create', Toner::class);
    }

    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(['NUEVO', 'DISPONIBLE', 'BAJO', 'AGOTADO'])],
            'comments' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['low_stock_threshold' => 2]);
    }
}
