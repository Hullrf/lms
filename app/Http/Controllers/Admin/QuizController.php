<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function edit(Lesson $lesson)
    {
        $this->authorize('update', $lesson->module->course);
        $lesson->load('questions.options', 'module.course');
        return view('admin.quiz.edit', compact('lesson'));
    }

    public function storeQuestion(Request $request, Lesson $lesson)
    {
        $this->authorize('update', $lesson->module->course);
        $data = $request->validate(['question' => 'required|string|max:500']);
        $data['sort_order'] = $lesson->questions()->count();
        $lesson->questions()->create($data);
        return back()->with('success', 'Pregunta añadida.');
    }

    public function destroyQuestion(QuizQuestion $question)
    {
        $this->authorize('update', $question->lesson->module->course);
        $question->delete();
        return back()->with('success', 'Pregunta eliminada.');
    }

    public function storeOption(Request $request, QuizQuestion $question)
    {
        $this->authorize('update', $question->lesson->module->course);
        $data = $request->validate([
            'text'       => 'required|string|max:300',
            'is_correct' => 'boolean',
        ]);
        $question->options()->create($data);
        return back()->with('success', 'Opción añadida.');
    }

    public function destroyOption(QuizOption $option)
    {
        $this->authorize('update', $option->question->lesson->module->course);
        $option->delete();
        return back()->with('success', 'Opción eliminada.');
    }
}
