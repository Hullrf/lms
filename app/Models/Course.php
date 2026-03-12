<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'instructor_id', 'category_id', 'title', 'slug',
        'description', 'thumbnail', 'intro_video',
        'price', 'is_free', 'level', 'status', 'published_at',
    ];

    protected $casts = [
        'is_free'      => 'boolean',
        'price'        => 'decimal:2',
        'published_at' => 'datetime',
    ];

    // Relaciones
    public function instructor() {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function modules() {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function lessons() {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function enrollments() {
        return $this->hasMany(Enrollment::class);
    }

    public function students() {
        return $this->belongsToMany(User::class, 'enrollments')
                    ->withPivot('progress', 'enrolled_at', 'completed_at');
    }

    public function orders()       { return $this->hasMany(Order::class); }
    public function certificates() { return $this->hasMany(Certificate::class); }
    public function reviews()      { return $this->hasMany(Review::class); }

    // Helpers
    public function isEnrolledBy(User $user): bool {
        return $this->enrollments()->where('user_id', $user->id)->exists();
    }

    public function isFree(): bool {
        return $this->is_free || $this->price == 0;
    }

    public function getAverageRatingAttribute(): float {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    // Scopes
    public function scopePublished($query) {
        return $query->where('status', 'published');
    }

    public function scopeFree($query) {
        return $query->where('is_free', true);
    }
}