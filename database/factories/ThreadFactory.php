<?php

namespace HalilCosdu\ChatBot\Database\Factories;

use HalilCosdu\ChatBot\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thread>
 */
class ThreadFactory extends Factory
{
    protected $model = Thread::class;

    public function definition(): array
    {
        return [
            'owner_id' => null,
            'subject' => $this->faker->sentence(4),
            'remote_conversation_id' => null,
        ];
    }
}
