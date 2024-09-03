<?php

namespace App\Http\Controllers\Skilly;

use App\Http\Controllers\Controller;
use App\Models\Skilly\QuizQuestion;
use App\Models\Skilly\QuizTopic;
use App\Traits\OpenAICommunication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuizController extends Controller
{
    use OpenAICommunication;

    private array $difficulties = ['Beginner', 'Intermediate', 'Advanced'];

    private array $counts = [5, 10, 15, 20];

    public function show()
    {
        $topics = QuizTopic::query()
            ->where('module_id', Auth::user()->module_id)
            ->get()->pluck('name');

        return Inertia::render('Skilly/Quiz', [
            'topics' => $topics,
            'difficulties' => $this->difficulties,
            'counts' => $this->counts,
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|exists:skilly_quiz_topics,name',
            'difficulty' => ['required', 'string', Rule::in($this->difficulties)],
            'count' => ['required', 'integer', Rule::in($this->counts)],
        ]);

        $topic = QuizTopic::query()
            ->where('name', $request->input('topic'))
            ->where('module_id', Auth::user()->module_id)
            ->first();

        if (!$topic) {
            return response()->json(['message' => 'You are not allowed to create a quiz for this topic'], 403);
        }

        $questions = [];

        for ($i = 0; $i < $request->input('count'); $i++) {
            $data = $this->getQuizDataFromOpenAI($topic->name, $request->input('difficulty'));

            $json = json_decode($data['choices'][0]['message']['content'], true);

            $question = QuizQuestion::query()->create([
                'question' => $json['question'],
                'correct_answer' => $json['correct_answer'],
                'wrong_answer_a' => $json['wrong_answer_a'],
                'wrong_answer_b' => $json['wrong_answer_b'],
                'wrong_answer_c' => $json['wrong_answer_c'],
                'description' => $json['description'],
                'prompt_tokens' => $data['usage']['prompt_tokens'],
                'completion_tokens' => $data['usage']['completion_tokens'],
                'openai_language_model' => 'gpt-4o-mini',
            ]);

            $questions[] = $question->only(['question', 'correct_answer', 'wrong_answer_a', 'wrong_answer_b', 'wrong_answer_c', 'description', 'id']);
        }

        return response()->json($questions);
    }
}
