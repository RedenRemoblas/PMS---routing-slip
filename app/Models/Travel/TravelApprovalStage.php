<?php

namespace App\Models\Travel;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelApprovalStage extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural form of the model name
    protected $table = 'travel_approval_stages';

    // Define the fillable properties
    protected $fillable = [
        'employee_id',
        'travel_order_id',

        'status',
        'remarks',
        'sequence',
    ];

    // Define relationships

    /**
     * Get the employee associated with the travel approval stage.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the travel order associated with the approval stage.
     */
    public function travelOrder()
    {
        return $this->belongsTo(TravelOrder::class);
    }


    public function approve()
    {
        $this->update(['status' => 'approved']);

        $nextStage = $this->travelOrder->approvalStages()
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();

        if ($nextStage === null) {
            $this->update(['status' => 'approved']);
            $this->travelOrder->update(['status' => 'approved']);
            return 'approved';
        } else {
            $this->update(['status' => 'endorsed']);
            $nextStage->update(['status' => 'pending']);
            return 'endorsed';
        }
    }

    public function getIsNextAttribute()
    {
        $nextStage = $this->travelOrder->approvalStages()
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();

        return $nextStage && $nextStage->id === $this->id;
    }
}
