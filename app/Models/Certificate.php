<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'course_id', 'code', 'issued_at', 'file_path'];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
}