<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_name',
        'accrual_rate',

        'expiration_days',
        'fixed_expiry',
        'frequency',
        'notes',
    ];

    public function leaveAccruals()
    {
        return $this->hasMany(LeaveAccrual::class);
    }

    public function leaveUsages()
    {
        return $this->hasMany(LeaveUsage::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
