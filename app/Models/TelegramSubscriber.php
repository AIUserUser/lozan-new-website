<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_id', 'username', 'first_name', 'last_name', 'active'])]
class TelegramSubscriber extends Model
{
    protected function casts(): array
    {
        return [
            'chat_id' => 'integer',
            'active' => 'boolean',
        ];
    }
}
