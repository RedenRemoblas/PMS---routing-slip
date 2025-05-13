<?php

namespace Database\Factories\Hr;

use App\Models\Employee;
use App\Models\Hr\LeaveCredit;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveCreditFactory extends Factory
{
    protected $model = LeaveCredit::class;

    public function definition()
    {
        return [
            'date_credited' => $this->faker->date(),
            'employee_id' => Employee::inRandomOrder()->first()->id, // Get a random employee ID from the existing employees
            'type' => $this->faker->randomElement(['sick', 'vacation', 'personal']),
            'qty' => $this->faker->numberBetween(1, 30),
            'expiry' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
        ];
    }
}
