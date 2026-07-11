<?php

namespace HalilCosdu\ChatBot\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class CustomThread extends Model
{
    protected $table = 'threads';

    protected $fillable = ['owner_id', 'subject', 'remote_conversation_id'];
}
