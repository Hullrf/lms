<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('lesson')->module->course);
    }

    public function rules(): array
    {
        return [
            'title'          => 'required|string|max:200',
            'content'        => 'nullable|string',
            'video_url'      => 'required_if:type,video|nullable|url|max:500',
            'video_duration' => 'nullable|integer|min:0',
            'type'           => 'required|in:video,text,quiz,file',
            'is_preview'     => 'nullable|boolean',
            'sort_order'     => 'nullable|integer|min:0',
            'passing_score'  => 'nullable|integer|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'         => 'El título de la lección es obligatorio.',
            'title.max'              => 'El título no puede superar los 200 caracteres.',
            'video_url.required_if'  => 'La URL del video es obligatoria para lecciones de tipo video.',
            'video_url.url'          => 'La URL del video debe ser una dirección web válida.',
            'video_url.max'          => 'La URL del video no puede superar los 500 caracteres.',
            'type.required'          => 'El tipo de lección es obligatorio.',
            'type.in'                => 'El tipo debe ser: video, texto, quiz o archivo.',
            'video_duration.integer' => 'La duración debe ser un número entero de segundos.',
            'video_duration.min'     => 'La duración no puede ser negativa.',
            'passing_score.integer'  => 'La nota mínima debe ser un número entero.',
            'passing_score.min'      => 'La nota mínima no puede ser menor a 0.',
            'passing_score.max'      => 'La nota mínima no puede superar 100.',
        ];
    }
}
