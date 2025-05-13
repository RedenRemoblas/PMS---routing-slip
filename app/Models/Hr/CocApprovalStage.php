<?php

namespace App\Models\Hr;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CocApprovalStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coc_application_id',
        'employee_id',

        'status',
        'remarks',
        'sequence',
    ];

    public function approve()
    {
        $this->update(['status' => 'approved']);

        // Get the next approval stage in sequence
        $nextStage = self::where('coc_application_id', $this->coc_application_id)
            ->where('sequence', '>', $this->sequence)
            ->orderBy('sequence')
            ->first();

        if ($nextStage) {
            // If there's a next stage, set it as pending (or any other initial status)
            $nextStage->update(['status' => 'pending']);
        } else {
            // If there's no next stage, mark the application as approved
            $this->cocApplication->update(['status' => 'approved']);
        }
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);

        // Mark the entire application as rejected
        $this->cocApplication->update(['status' => 'rejected']);
    }

    public function cocApplication()
    {
        return $this->belongsTo(CocApplication::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
