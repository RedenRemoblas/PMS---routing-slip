<?php

namespace App\Models\Travel;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelOrderDetail extends Model
{
    use HasFactory;

    protected $table = 'travel_order_details';

    protected $fillable = [
        'travel_order_id',
        'employee_id',
        'position',
        'division',

    ];

    /**
     * Get the travel order that owns the detail.
     */
    public function travelOrder()
    {
        return $this->belongsTo(TravelOrder::class);
    }

    /**
     * Get the employee associated with this travel order detail.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
