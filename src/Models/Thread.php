<?php

namespace HalilCosdu\ChatBot\Models;

use HalilCosdu\ChatBot\Database\Factories\ThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $owner_id
 * @property string $subject
 * @property string|null $remote_conversation_id
 * @property string|null $remote_thread_id Legacy Assistants API id; kept for the v1 -> v2 migration only.
 */
class Thread extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'subject', 'remote_conversation_id', 'remote_thread_id'];

    protected static function newFactory(): ThreadFactory
    {
        return ThreadFactory::new();
    }

    public function threadMessages(): HasMany
    {
        return $this->hasMany(config('chatbot.models.thread_messages', ThreadMessage::class), 'thread_id');
    }
}
