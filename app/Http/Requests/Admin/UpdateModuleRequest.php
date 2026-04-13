<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('module')->course);
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'     => 'El título del módulo es obligatorio.',
            'title.max'          => 'El título no puede superar los 200 caracteres.',
            'description.max'    => 'La descripción no puede superar los 1000 caracteres.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min'     => 'El orden no puede ser negativo.',
        ];
    }
}
