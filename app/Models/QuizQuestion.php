<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $fillable = ['lesson_id', 'question', 'sort_order'];

    public function lesson()  { return $this->belongsTo(Lesson::class); }
    public function options() { return $this->hasMany(QuizOption::class, 'question_id'); }
}
