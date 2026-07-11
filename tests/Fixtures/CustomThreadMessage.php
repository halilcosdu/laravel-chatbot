<?php

namespace HalilCosdu\ChatBot\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class CustomThreadMessage extends Model
{
    protected $table = 'thread_messages';

    protected $fillable = ['thread_id', 'role', 'content'];
}
