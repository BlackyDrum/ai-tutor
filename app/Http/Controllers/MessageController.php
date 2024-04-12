<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\SharedConversation;
use App\Traits\AppSupportTraits;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class MessageController extends Controller
{
    use AppSupportTraits;

    private function fetchMessagesByType($conversation_id, $type)
    {
        $methodName = 'getMessagesFor' . ucfirst($type);

        if (!method_exists($this, $methodName)) {
            return response()->json(
                ['message' => 'Invalid fetch type specified'],
                422
            );
        }

        $messages = $this->$methodName($conversation_id);

        if (!$messages) {
            return response()->json(
                ['message' => 'The selected conversation id is invalid'],
                422
            );
        }

        return response()->json($messages);
    }

    public function fetchMessagesForChat($conversation_id)
    {
        return $this->fetchMessagesByType($conversation_id, 'chat');
    }

    public function fetchMessagesForPeek($conversation_id)
    {
        return $this->fetchMessagesByType($conversation_id, 'peek');
    }

    public function fetchMessagesForShare($conversation_id)
    {
        return $this->fetchMessagesByType($conversation_id, 'share');
    }

    public static function getMessagesForChat($conversation_id)
    {
        $conversation = Conversation::query()
            ->where('url_id', $conversation_id)
            ->first();

        if (!$conversation || $conversation->user_id !== Auth::id()) {
            return false;
        }

        return Message::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->select(['id', 'user_message', 'agent_message', 'helpful'])
            ->paginate(
                self::isMobile(\request()->userAgent())
                    ? config('chat.messages_per_page_mobile')
                    : config('chat.messages_per_page_desktop')
            );
    }

    public static function getMessagesForShare($conversation_id)
    {
        $shared = SharedConversation::query()
            ->where('shared_url_id', '=', $conversation_id)
            ->first();

        if (!$shared) {
            return false;
        }

        $conversation = Conversation::query()->find($shared->conversation_id);

        return SharedConversation::query()
            ->join(
                'conversations',
                'conversations.id',
                '=',
                'shared_conversations.conversation_id'
            )
            ->join(
                'messages',
                'messages.conversation_id',
                '=',
                'conversations.id'
            )
            ->where('conversations.id', '=', $conversation->id)
            ->whereRaw('messages.created_at < shared_conversations.created_at')
            ->orderBy('messages.created_at', 'desc')
            ->select(['messages.user_message', 'messages.agent_message'])
            ->paginate(
                self::isMobile(\request()->userAgent())
                    ? config('chat.messages_per_page_mobile')
                    : config('chat.messages_per_page_desktop')
            );
    }

    public static function getMessagesForPeek($conversation_id)
    {
        $conversation = Conversation::withTrashed()
            ->where('url_id', $conversation_id)
            ->first();

        if (!$conversation) {
            return false;
        }

        return Message::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->paginate(
                self::isMobile(\request()->userAgent())
                    ? config('chat.messages_per_page_mobile')
                    : config('chat.messages_per_page_desktop')
            );
    }

    public function updateRating(Request $request)
    {
        $request->validate([
            'helpful' => 'required|boolean',
            'message_id' => 'required|string',
        ]);

        $id = Hashids::decode($request->input('message_id'));

        try {
            Message::query()->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                ['message_id' => 'The selected message id is invalid'],
                422
            );
        }

        $message = Message::query()
            ->where('messages.id', '=', $id)
            ->join(
                'conversations',
                'conversations.id',
                '=',
                'messages.conversation_id'
            )
            ->where('conversations.user_id', '=', Auth::id())
            ->select(['messages.*'])
            ->first();

        if (!$message) {
            return response()->json(
                ['message' => 'The selected message id is invalid'],
                422
            );
        }

        $message->update([
            'helpful' => $request->input('helpful'),
        ]);

        return response()->json([
            'id' => $request->input('message_id'),
            'helpful' => $request->input('helpful'),
        ]);
    }
}
