<?php


namespace App\Models\Hr;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'days_remaining',
        'days_reserved',
    ];

    protected $appends = ['days_available'];

    /**
     * Accessor for days_available.
     */
    public function getDaysAvailableAttribute()
    {
        return $this->days_remaining - $this->days_reserved;
    }


    /**
     * Prevent direct creation of LeaveBalance.
     */
    public static function create(array $attributes = [])
    {
        throw new \Exception('LeaveBalance cannot be created directly. Use LeaveAccrual to update balances.');
    }


    /**
     * Custom method to update LeaveBalance via LeaveAccrual.
     */
    public static function updateBalanceFromAccrual($employeeId, $leaveTypeId, $daysAccrued)
    {
        $leaveBalance = self::firstOrNew([
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
        ]);

        $leaveBalance->days_remaining += $daysAccrued;
        $leaveBalance->save();

        return $leaveBalance;
    }
    /**
     * Mutators for validating inputs.
     */
    public function setDaysRemainingAttribute($value)
    {
        $this->attributes['days_remaining'] = max(0, $value);
    }

    public function setDaysReservedAttribute($value)
    {
        $this->attributes['days_reserved'] = max(0, $value);
    }

    /**
     * Relationships.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'id');
    }

    /**
     * Scope for active leave balances.
     */
    public function scopeActive($query)
    {
        return $query->where('days_remaining', '>', 0);
    }

    /**
     * Boot method for adding model events.
     */
    protected static function booted()
    {
        static::saving(function ($leaveBalance) {
            if ($leaveBalance->days_reserved > $leaveBalance->days_remaining) {
                throw new \Exception('Reserved days cannot exceed remaining days.');
            }
        });
    }
}
