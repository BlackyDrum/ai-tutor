<?php

namespace App\Http\Controllers\Skilly;

use App\Http\Controllers\Controller;
use App\Models\Skilly\QuizTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuizController extends Controller
{
    private array $difficulties = ['Beginner', 'Intermediate', 'Advanced'];

    public function show()
    {
        $topics = QuizTopic::query()
            ->where('module_id', Auth::user()->module_id)
            ->get()->pluck('name');

        return Inertia::render('Skilly/Quiz', [
            'topics' => $topics,
            'difficulties' => $this->difficulties,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|exists:skilly_quiz_topics,name',
            'difficulty' => ['required', 'string', Rule::in($this->difficulties)],
        ]);

        $topic = QuizTopic::query()
            ->where('name', $request->input('topic'))
            ->where('module_id', Auth::user()->module_id)
            ->first();

        if (!$topic) {
            return response()->json(['message' => 'You are not allowed to create a quiz for this topic'], 403);
        }


    }
}
