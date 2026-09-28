<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST') && ! $this->has('_method');

        return [
            'alias_name' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string'],
            'file' => [$isCreate ? 'required' : 'nullable', 'file'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio al subir.',
            'file.file' => 'Debe proporcionar un archivo válido.',
            'alias_name.max' => 'El nombre descriptivo no puede exceder de 255 caracteres.',
        ];
    }
}
