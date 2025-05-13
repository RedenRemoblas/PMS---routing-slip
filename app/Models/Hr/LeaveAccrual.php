<?php

namespace App\Models\Hr;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveAccrual extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'accrual_date',
        'days_accrued',
        'expiry_date',
    ];



    /**
     * Centralized method to create leave accrual and update the corresponding leave balance.
     *
     * @param array $data
     * @return self
     */
    public static function createAccrual(array $data): self
    {
        Log::info('calling createAccrual called with data', $data);

        try {
            return DB::transaction(function () use ($data) {
                Log::info('createAccrual called with data', $data);

                $leaveAccrual = parent::create($data);

                $leaveBalance = LeaveBalance::firstOrNew([
                    'employee_id' => $data['employee_id'],
                    'leave_type_id' => $data['leave_type_id'],
                ]);

                $leaveBalance->days_remaining += $data['days_accrued'];
                $leaveBalance->save();

                Log::info('LeaveBalance updated', [
                    'employee_id' => $data['employee_id'],
                    'leave_type_id' => $data['leave_type_id'],
                    'days_remaining' => $leaveBalance->days_remaining,
                ]);

                return $leaveAccrual;
            });
        } catch (\Exception $e) {
            Log::error('Error in createAccrual', ['error' => $e->getMessage()]);
            throw $e; // Re-throw the exception to maintain expected behavior
        }
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
