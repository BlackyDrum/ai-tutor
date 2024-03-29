<?php

namespace App\Rules;

use App\Models\Conversation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class ValidateConversationOwner implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $conversation = Conversation::query()
            ->where('url_id', $value)
            ->first();

        if ($conversation->user_id !== Auth::id()) {
            $fail('The selected conversation id is invalid.');
        }
    }
}
