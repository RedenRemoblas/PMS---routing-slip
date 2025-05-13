<?php

namespace Database\Factories\Hr;

use App\Models\Hr\Leave;
use App\Models\Hr\LeaveDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveDetailFactory extends Factory
{
    protected $model = LeaveDetail::class;

    public function definition()
    {
        return [
            'leave_id' => Leave::inRandomOrder()->first()->id, // Get a random employee ID from the existing employees
            'leave_date' => $this->faker->date(),
            'period' => $this->faker->randomElement(['am', 'pm', 'wd']),
            'qty' => $this->faker->numberBetween(1, 8),
        ];
    }
}
