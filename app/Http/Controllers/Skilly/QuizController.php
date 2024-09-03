<?php

namespace App\Http\Controllers\Skilly;

use App\Http\Controllers\Controller;
use App\Models\Skilly\QuizTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class QuizController extends Controller
{
    public function show()
    {
        $topics = QuizTopic::query()->where('module_id', Auth::user()->module_id)->get();

        return Inertia::render('Skilly/Quiz', [
            'topics' => $topics
        ]);
    }
}
