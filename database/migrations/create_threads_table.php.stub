<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->string('owner_id')->nullable()->index();
            $table->string('subject');
            $table->string('remote_thread_id')->index();

            $table->timestamps();
        });
    }
};
