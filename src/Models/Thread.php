<?php

namespace HalilCosdu\ChatBot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $owner_id
 * @property string $subject
 * @property string $remote_thread_id
 */
class Thread extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'subject', 'remote_thread_id'];

    public function threadMessages(): HasMany
    {
        return $this->hasMany(config('chatbot.models.thread_messages', ThreadMessage::class));
    }
}
