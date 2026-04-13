<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'module_id', 'title', 'slug', 'content',
        'video_url', 'video_duration', 'type',
        'is_preview', 'sort_order', 'passing_score',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
    ];

    public function module() {
        return $this->belongsTo(Module::class);
    }

    public function course() {
        return $this->hasOneThrough(Course::class, Module::class,
            'id', 'id', 'module_id', 'course_id');
    }

    public function resources() {
        return $this->hasMany(LessonResource::class);
    }

    public function progressRecords() {
        return $this->hasMany(LessonProgress::class);
    }

    public function isCompletedBy(User $user): bool {
        return $this->progressRecords()
                    ->where('user_id', $user->id)
                    ->where('completed', true)
                    ->exists();
    }

    public function questions() {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    public function passingScore(): int
    {
        return $this->passing_score ?? 70;
    }
}