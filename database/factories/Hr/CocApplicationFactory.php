<?php

namespace Database\Factories\Hr;

use App\Models\Employee;
use App\Models\Hr\CocApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CocApplicationFactory extends Factory
{
    protected $model = CocApplication::class;

    public function definition()
    {
        return [
            'date_filed' => Carbon::now(),
            'employee_id' => Employee::factory(),
            'description' => $this->faker->sentence,
            'status' => 'pending',
        ];
    }
}
