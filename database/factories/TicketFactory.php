<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Enums\PostStatus;
use App\Enums\PostPriority;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userIds = \App\Models\User::pluck('id')->toArray();

        return [
            'title' => fake()->sentence,

            'reporter_id' =>   $this->faker->randomElement($userIds) ,

            'description' => fake()->paragraph,

            'status' => fake()->randomElement(PostStatus::cases()),

            'priority' => fake()->randomElement(PostPriority::cases()),
            'completed_at' => fake()->date(),
        ];
    }
}
