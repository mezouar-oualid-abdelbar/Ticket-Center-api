<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
            $userIds = \App\Models\User::pluck('id')->toArray();
            $ticketIds = \App\Models\Ticket::pluck('id')->toArray();

        return [

            'ticket_id' =>   $this->faker->randomElement($ticketIds) ,

            'sender_id' =>  $this->faker->randomElement($userIds) ,

            'message' =>  fake()->sentence,


        ];
    }
}
