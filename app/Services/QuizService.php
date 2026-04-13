<?php

namespace App\Services;

use App\Models\Lesson;

class QuizService
{
    public function grade(Lesson $lesson, array $answers): array
    {
        $questions = $lesson->questions()->with('options')->orderBy('sort_order')->get();
        $total     = $questions->count();
        $correct   = 0;
        $results   = [];

        foreach ($questions as $question) {
            $selectedId    = isset($answers[$question->id]) ? (int) $answers[$question->id] : null;
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect     = $selectedId !== null
                && $correctOption !== null
                && $selectedId === $correctOption->id;

            if ($isCorrect) {
                $correct++;
            }

            $results[$question->id] = [
                'correct'        => $isCorrect,
                'selected'       => $selectedId,
                'correct_option' => $correctOption?->id,
            ];
        }

        $score  = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $passed = $score >= $lesson->passingScore();

        return [
            'score'   => $score,
            'passed'  => $passed,
            'correct' => $correct,
            'total'   => $total,
            'results' => $results,
        ];
    }
}
