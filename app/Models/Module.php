<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;
    protected $fillable = ['course_id', 'title', 'description', 'sort_order'];

    protected static function boot(): void
    {
        parent::boot();
        static::deleting(function (Module $module) {
            $module->lessons->each->delete();
        });
    }

    public function course() {
        return $this->belongsTo(Course::class);
    }

    public function lessons() {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }
}