<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Intervention>
 */
class InterventionFactory extends Factory
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

            'appointment' => fake()->date(),

            'ticket_id' =>   $this->faker->randomElement($ticketIds) ,

            'leader_id' =>  $this->faker->randomElement($userIds) ,

            'note' =>  fake()->sentence,


        ];
    }
}
