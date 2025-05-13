<?php

namespace Database\Factories\Hr;

use App\Models\Employee;
use App\Models\Hr\Leave;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition()
    {
        return [
            'date_filed' => $this->faker->date(),
            'employee_id' => Employee::inRandomOrder()->first()->id, // Get a random employee ID from the existing employees
            'type' => $this->faker->randomElement(['sick', 'vacation', 'emergency']),
            'type_description' => $this->faker->words(3, true),

            'details' => $this->faker->words(3, true),
            'description' => $this->faker->words(3, true),
            'commutation' => $this->faker->randomElement(['yes', 'no']),
            'total_days' => $this->faker->numberBetween(1, 15),
            'leave_status' => $this->faker->randomElement(['pending', 'disapproved', 'approved']),
        ];
    }
}
