<?php

namespace App\Models\Hr;

use App\Models\Travel\TravelOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CocApplicationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'coc_application_id',
        'date_earned',
        'hours_earned',
        'travel_order_id',
        'overtime_order_id',
    ];

    public function cocApplication()
    {
        return $this->belongsTo(CocApplication::class);
    }

    public function travelOrder()
    {
        return $this->belongsTo(TravelOrder::class);
    }

    public function overtimeOrder()
    {
        return $this->belongsTo(OvertimeOrder::class);
    }
}
