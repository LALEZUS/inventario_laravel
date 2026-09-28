<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TutorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST') && !$this->has('_method') && $this->routeIs('api.v1.tutorials.store');

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
            'pdf_file' => [
                $isCreate ? 'required' : 'nullable',
                'file',
                'mimes:pdf',
                'max:30720', // 30 MB max (30,720 KB)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del tutorial es obligatorio.',
            'title.max' => 'El título no puede exceder los 255 caracteres.',
            'category.max' => 'La categoría no puede exceder los 100 caracteres.',
            'pdf_file.required' => 'El archivo PDF es obligatorio para registrar el tutorial.',
            'pdf_file.file' => 'El archivo debe ser un documento válido.',
            'pdf_file.mimes' => 'El archivo debe ser en formato PDF (.pdf).',
            'pdf_file.max' => 'El archivo PDF no debe pesar más de 30 MB.',
        ];
    }
}