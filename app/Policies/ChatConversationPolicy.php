<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;

class ChatConversationPolicy
{
    public function view(User $user, ChatConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    public function update(User $user, ChatConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }

    public function delete(User $user, ChatConversation $conversation): bool
    {
        return $conversation->user_id === $user->id;
    }
}
