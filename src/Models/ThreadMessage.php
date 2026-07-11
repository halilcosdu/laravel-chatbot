<?php

namespace HalilCosdu\ChatBot\Models;

use HalilCosdu\ChatBot\Database\Factories\ThreadMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $thread_id
 * @property string $role
 * @property string $content
 */
class ThreadMessage extends Model
{
    use HasFactory;

    protected $fillable = ['thread_id', 'role', 'content'];

    protected static function newFactory(): ThreadMessageFactory
    {
        return ThreadMessageFactory::new();
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(config('chatbot.models.thread', Thread::class), 'thread_id');
    }
}
