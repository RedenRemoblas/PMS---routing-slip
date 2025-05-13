<?php

namespace Database\Factories\Hr;

use App\Models\Hr\CocApplication;
use App\Models\Hr\CocApplicationDetail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CocApplicationDetailFactory extends Factory
{
    protected $model = CocApplicationDetail::class;

    public function definition()
    {
        return [
            'coc_application_id' => CocApplication::factory(),
            'date_earned' => Carbon::now()->subDays(rand(1, 10)),
            'hours_earned' => $this->faker->numberBetween(4, 8),
            'travel_order_id' => null,
            'overtime_order_id' => null,
        ];
    }
}
