<?php

namespace App\Nova\Cards;

use App\Models\Message;
use Vinkla\Hashids\Facades\Hashids;
use Whitespacecode\TableCard\TableCard;
use Whitespacecode\TableCard\Table\Cell;
use Whitespacecode\TableCard\Table\Row;

class LatestMessages extends TableCard
{
    public function __construct()
    {
        parent::__construct();

        $this->title('Latest User Messages');

        $header = collect(['User', 'User Message']);

        $this->header(
            $header
                ->map(function ($value) {
                    return Cell::make($value);
                })
                ->toArray()
        );

        $messages = Message::query()
            ->join(
                'conversations',
                'conversations.id',
                'messages.conversation_id'
            )
            ->join('users', 'users.id', '=', 'conversations.user_id')
            ->select(['messages.user_message', 'messages.id', 'users.name'])
            ->latest('messages.created_at')
            ->paginate(5);

        $this->paginator($messages);

        $this->data(
            $messages
                ->map(function ($message) {
                    $id = Hashids::decode($message['id'])[0];

                    return Row::make(
                        Cell::make($message['name']),
                        Cell::make(
                            htmlspecialchars(
                                substr($message['user_message'], 0, 128) .
                                    (strlen($message['user_message']) > 128
                                        ? '...'
                                        : '')
                            )
                        )
                    )->viewLink("/admin/resources/messages/$id");
                })
                ->toArray()
        );
    }
}
