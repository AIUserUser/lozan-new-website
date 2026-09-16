<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed allowlist of Telegram chats that receive order notifications.
 * `active` is false until the person has started the bot (or after /stop).
 */
#[Fillable(['chat_id', 'label', 'username', 'first_name', 'last_name', 'active'])]
class TelegramSubscriber extends Model
{
    protected function casts(): array
    {
        return [
            'chat_id' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function displayName(): string
    {
        $name = $this->label ?: trim($this->first_name.' '.$this->last_name);

        return $name !== '' ? $name : ($this->username ? '@'.$this->username : (string) $this->chat_id);
    }
}
