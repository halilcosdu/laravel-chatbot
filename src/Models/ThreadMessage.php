<?php

namespace HalilCosdu\ChatBot\Models;

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

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }
}
