<?php

namespace HalilCosdu\ChatBot\Database\Factories;

use HalilCosdu\ChatBot\Models\ThreadMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThreadMessage>
 */
class ThreadMessageFactory extends Factory
{
    protected $model = ThreadMessage::class;

    public function definition(): array
    {
        return [
            'role' => 'user',
            'content' => $this->faker->sentence(),
        ];
    }
}
