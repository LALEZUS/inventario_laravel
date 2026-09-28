<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST') && !$this->has('_method') && $this->routeIs('api.v1.gallery.store');

        return [
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'image' => [
                $isCreate ? 'required' : 'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:15360', // 15 MB max
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la imagen es obligatorio.',
            'title.max' => 'El título no puede exceder los 255 caracteres.',
            'image.required' => 'La imagen es obligatoria.',
            'image.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'image.mimes' => 'La imagen debe ser de tipo: jpg, jpeg, png, gif o webp.',
            'image.max' => 'La imagen no debe pesar más de 15 MB.',
        ];
    }
}