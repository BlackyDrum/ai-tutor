<?php

namespace App\Http\Middleware;

use App\Models\Collections;
use App\Models\Conversations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $conversations = [];

        if (!str_starts_with($request->path(), 'admin') && Auth::check()) {
            $conversations = Conversations::query()
                ->where('user_id', '=', Auth::id())
                ->leftJoin(
                    'shared_conversations',
                    'shared_conversations.conversation_id',
                    '=',
                    'conversations.id'
                )
                ->orderBy('updated_at', 'desc')
                ->select([
                    'conversations.*',
                    'shared_conversations.shared_url_id',
                ])
                ->get();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'history' => $conversations,
            ],
        ];
    }
}
