<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'id';

    protected $fillable = ['user_id', 'course_id', 'progress', 'enrolled_at', 'completed_at'];

    protected $casts = [
        'enrolled_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }

    public function isCompleted(): bool {
        return $this->progress === 100 && $this->completed_at !== null;
    }
}