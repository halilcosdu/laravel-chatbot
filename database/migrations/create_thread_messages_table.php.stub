<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('thread_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(config('chatbot.models.thread'))->constrained()->cascadeOnDelete();
            $table->string('role')->index();
            $table->longText('content');

            $table->timestamps();
        });
    }
};
