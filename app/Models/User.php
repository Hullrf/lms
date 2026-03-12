<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'bio',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // Roles
    public function isAdmin(): bool      { return $this->role === 'admin'; }
    public function isInstructor(): bool { return $this->role === 'instructor'; }
    public function isStudent(): bool    { return $this->role === 'student'; }

    // Relaciones
    public function courses()     { return $this->hasMany(Course::class, 'instructor_id'); }
    public function enrollments() { return $this->hasMany(Enrollment::class); }
    public function orders()      { return $this->hasMany(Order::class); }
    public function certificates(){ return $this->hasMany(Certificate::class); }
    public function reviews()     { return $this->hasMany(Review::class); }
    public function progress()    { return $this->hasMany(LessonProgress::class); }

    public function enrolledCourses() {
        return $this->belongsToMany(Course::class, 'enrollments')
                    ->withPivot('progress', 'enrolled_at', 'completed_at')
                    ->withTimestamps();
    }

    public function isEnrolledIn(Course $course): bool {
        return $this->enrollments()->where('course_id', $course->id)->exists();
    }
}