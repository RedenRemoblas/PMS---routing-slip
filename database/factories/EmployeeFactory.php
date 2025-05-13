<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Setup\Division;
use App\Models\Setup\Position;
use App\Models\Setup\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        return [
            'firstname' => $this->faker->firstName(),
            'middlename' => $this->faker->optional()->firstName(),
            'lastname' => $this->faker->lastName(),
            'employee_no' => $this->faker->unique()->numerify('EMP####') ?? null,

            'civil_status' => $this->faker->randomElement(['single', 'married', 'widowed', 'divorced']),
            'employment_status' => $this->faker->randomElement(['jo', 'regular', 'probationary']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'designation' => $this->faker->optional()->jobTitle(),
            'division_id' => Division::inRandomOrder()->first()->id, // Get a random employee ID from the existing employees
            'position_id' => Position::inRandomOrder()->first()->id, // Get a random employee ID from the existing employees
            'project_id' => Project::inRandomOrder()->first()->id, // Get a random employee ID from the existing employees
            'birthday' => $this->faker->date(),
            'mobile' => $this->faker->phoneNumber(),
            'gsis_no' => $this->faker->unique()->numerify('GSIS####') ?? null,
            'tin' => $this->faker->unique()->numerify('TIN####') ?? null,
            'supervisor' => $this->faker->optional()->name(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'entrance_to_duty' => $this->faker->optional()->date(),
            'region' => $this->faker->randomElement(['CAR', 'Region 1', 'Region 2']),
            'office' => $this->faker->optional()->randomElement(['Region Office', 'Abra', 'Apayao', 'Benguet', 'Ifugao', 'Kalinga', 'Mt. Province']),
            //       'photo' => $this->faker->phoneNumber(),
            'user_id' => User::factory(),
        ];
    }
}
