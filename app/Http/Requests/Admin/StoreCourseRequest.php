<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Course::class);
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'level'       => 'required|in:beginner,intermediate,advanced',
            'status'      => 'required|in:draft,published,archived',
            'is_free'     => 'boolean',
            'price'       => 'required_if:is_free,0|nullable|numeric|min:0',
            'intro_video' => 'nullable|url|max:500',
            'thumbnail'   => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'El título del curso es obligatorio.',
            'title.max'            => 'El título no puede superar los 200 caracteres.',
            'price.required_if'    => 'El precio es obligatorio cuando el curso no es gratuito.',
            'price.numeric'        => 'El precio debe ser un valor numérico.',
            'price.min'            => 'El precio no puede ser negativo.',
            'level.required'       => 'El nivel del curso es obligatorio.',
            'level.in'             => 'El nivel debe ser: principiante, intermedio o avanzado.',
            'status.required'      => 'El estado del curso es obligatorio.',
            'status.in'            => 'El estado debe ser: borrador, publicado o archivado.',
            'thumbnail.image'      => 'La miniatura debe ser una imagen.',
            'thumbnail.max'        => 'La imagen no puede superar los 2 MB.',
            'intro_video.url'      => 'El video introductorio debe ser una URL válida.',
            'intro_video.max'      => 'La URL del video no puede superar los 500 caracteres.',
            'category_id.exists'   => 'La categoría seleccionada no existe.',
        ];
    }
}
