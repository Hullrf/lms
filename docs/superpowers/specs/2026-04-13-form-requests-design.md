# FormRequests Admin — Design Spec
**Date:** 2026-04-13
**Status:** Approved

## Objetivo

Extraer la validación y autorización de los controladores admin (`CourseController`, `LessonController`, `ModuleController`) hacia clases `FormRequest` dedicadas, con mensajes de error en español y validación condicional correcta.

---

## Problema actual

| Controlador | Problema |
|---|---|
| `CourseController::store/update` | `$request->validate()` + `$this->authorize()` inline. `price` siempre requerido aunque `is_free=true`. Sin mensajes en español. `intro_video` sin validación. |
| `LessonController::store/update` | `video_url` siempre `nullable` aunque `type=video`. `passing_score` sin validar (columna existe en BD). |
| `ModuleController::store/update` | `description` sin límite de longitud. Validación duplicada entre store y update. |

---

## Arquitectura

### Archivos nuevos

```
app/Http/Requests/Admin/
├── StoreCourseRequest.php
├── UpdateCourseRequest.php
├── StoreLessonRequest.php
├── UpdateLessonRequest.php
├── StoreModuleRequest.php
└── UpdateModuleRequest.php
```

### Archivos modificados

```
app/Http/Controllers/Admin/
├── CourseController.php    — store(), update()
├── LessonController.php    — store(), update()
└── ModuleController.php    — store(), update()
```

### Principio aplicado

Cada FormRequest implementa:
- `authorize(): bool` — reemplaza `$this->authorize()` del controlador
- `rules(): array` — reemplaza `$request->validate([...])`
- `messages(): array` — mensajes de error en español

El controlador cambia `Request $request` por el FormRequest específico y reemplaza `$request->validate([...])` por `$request->validated()`.

---

## StoreCourseRequest

**Ruta:** `POST /admin/courses`
**Autorización:** `$this->user()->can('create', Course::class)`

```php
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
```

**Validación condicional:** `required_if:is_free,0` — precio requerido solo cuando el curso no es gratuito.

---

## UpdateCourseRequest

**Ruta:** `PUT/PATCH /admin/courses/{course}`
**Autorización:** `$this->user()->can('update', $this->route('course'))`

Reglas idénticas a `StoreCourseRequest`. El controlador sigue manejando `published_at` y el slug — no son campos del formulario validados aquí.

---

## StoreLessonRequest

**Ruta:** `POST /admin/modules/{module}/lessons`
**Autorización:** `$this->user()->can('update', $this->route('module')->course)`

```php
public function rules(): array
{
    return [
        'title'          => 'required|string|max:200',
        'content'        => 'nullable|string',
        'video_url'      => 'required_if:type,video|nullable|url|max:500',
        'video_duration' => 'nullable|integer|min:0',
        'type'           => 'required|in:video,text,quiz,file',
        'is_preview'     => 'boolean',
        'sort_order'     => 'integer|min:0',
        'passing_score'  => 'nullable|integer|min:0|max:100',
    ];
}
```

**Validación condicional:** `required_if:type,video` — `video_url` requerida solo para lecciones de tipo video.
**Campo nuevo:** `passing_score` — validado como entero entre 0 y 100, nullable.

---

## UpdateLessonRequest

**Ruta:** `PUT/PATCH /admin/lessons/{lesson}`
**Autorización:** `$this->user()->can('update', $this->route('lesson')->module->course)`

Reglas idénticas a `StoreLessonRequest`.

---

## StoreModuleRequest

**Ruta:** `POST /admin/courses/{course}/modules`
**Autorización:** `$this->user()->can('update', $this->route('course'))`

```php
public function rules(): array
{
    return [
        'title'       => 'required|string|max:200',
        'description' => 'nullable|string|max:1000',
        'sort_order'  => 'integer|min:0',
    ];
}
```

**Mejora:** `description` ahora tiene `max:1000` (antes sin límite).

---

## UpdateModuleRequest

**Ruta:** `PUT/PATCH /admin/modules/{module}`
**Autorización:** `$this->user()->can('update', $this->route('module')->course)`

Reglas idénticas a `StoreModuleRequest`.

---

## Mensajes en español

### Course (compartidos entre Store y Update)

```php
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
```

### Lesson (compartidos entre Store y Update)

```php
public function messages(): array
{
    return [
        'title.required'        => 'El título de la lección es obligatorio.',
        'title.max'             => 'El título no puede superar los 200 caracteres.',
        'video_url.required_if' => 'La URL del video es obligatoria para lecciones de tipo video.',
        'video_url.url'         => 'La URL del video debe ser una dirección web válida.',
        'video_url.max'         => 'La URL del video no puede superar los 500 caracteres.',
        'type.required'         => 'El tipo de lección es obligatorio.',
        'type.in'               => 'El tipo debe ser: video, texto, quiz o archivo.',
        'video_duration.integer'=> 'La duración debe ser un número entero de segundos.',
        'video_duration.min'    => 'La duración no puede ser negativa.',
        'passing_score.integer' => 'La nota mínima debe ser un número entero.',
        'passing_score.min'     => 'La nota mínima no puede ser menor a 0.',
        'passing_score.max'     => 'La nota mínima no puede superar 100.',
    ];
}
```

### Module (compartidos entre Store y Update)

```php
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
```

---

## Controladores refactorizados

### CourseController

```php
// store()
public function store(StoreCourseRequest $request)
{
    $data = $request->validated();
    // resto sin cambios: Str::slug, thumbnail, published_at, Course::create
}

// update()
public function update(UpdateCourseRequest $request, Course $course)
{
    $data = $request->validated();
    // resto sin cambios: thumbnail, published_at, $course->update
}
```

Se eliminan: `$request->validate([...])` y `$this->authorize(...)` de ambos métodos.

### LessonController

```php
public function store(StoreLessonRequest $request, Module $module)
{
    $data = $request->validated();
    // resto sin cambios
}

public function update(UpdateLessonRequest $request, Lesson $lesson)
{
    $data = $request->validated();
    // resto sin cambios
}
```

### ModuleController

```php
public function store(StoreModuleRequest $request, Course $course)
{
    $data = $request->validated();
    // resto sin cambios
}

public function update(UpdateModuleRequest $request, Module $module)
{
    $data = $request->validated();
    // resto sin cambios
}
```

---

## Buenas prácticas aplicadas

| Práctica | Aplicación |
|---|---|
| **Single Responsibility** | Cada FormRequest tiene una sola responsabilidad: validar y autorizar una acción específica |
| **DRY** | Reglas duplicadas entre store/update eliminadas de los controladores |
| **Fail Fast** | Laravel rechaza la request antes de entrar al controlador si falla validación o autorización |
| **Mensajes localizados** | Mensajes en español directamente en el FormRequest, sin tocar archivos de lang |
| **Validación condicional** | `required_if` en lugar de lógica manual en el controlador |
| **`validated()`** | Solo los campos validados llegan al modelo — protección contra mass assignment accidental |

---

## Optimizaciones futuras (fuera de scope)

- Extraer mensajes compartidos a `lang/es/validation.php` para consistencia global
- Usar `prepareForValidation()` para normalizar datos antes de validar (ej: trim de strings)
- Trait `HasSpanishMessages` si hay muchos FormRequests con mensajes similares
