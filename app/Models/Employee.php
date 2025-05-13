<?php

namespace App\Models;

use App\Models\Hr\Dtr;
use App\Models\Hr\LeaveUsage;
use App\Models\Hr\LeaveAccrual;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\CocApplication;
use App\Models\Hr\OvertimeOrder;
use App\Models\Hr\OvertimeOrderDetail;
use App\Models\Travel\TravelOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $guarded = []; // 'guarded' should be used instead of 'guard'

    protected $appends = ['full_name'];

    public $incrementing = true;

    protected $fillable = [
        'id',
        'firstname',
        'middlename',
        'lastname',
        'employee_no',

        'position_id',
        'designation',
        'division_id',
        'entrance_to_duty',
        'gsis_no',
        'birthday',
        'gender',
        'civil_status',
        'mobile',
        'employment_status',
        'tin',
        'is_active',
        'project_id',
        'region',
        'office',
        'user_id',
        'supervisor',
        'device_serial_no',

    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where('lastname', 'like', '%' . $search . '%');
        })->when($filters['trashed'] ?? null, function ($query, $trashed) {
            if ($trashed === 'with') {
                $query->withTrashed();
            } elseif ($trashed === 'only') {
                $query->onlyTrashed();
            }
        });
    }

    public function getFullNameAttribute()
    {
        $middlenameInitial = $this->middlename ? ' ' . substr($this->middlename, 0, 1) . '.' : '';

        return ucwords(strtolower("{$this->lastname}, {$this->firstname}{$middlenameInitial}"));
    }

    public function position()
    {
        return $this->belongsTo('App\Models\Setup\Position');
    }

    public function division()
    {
        return $this->belongsTo('App\Models\Setup\Division');
    }

    public function project()
    {
        return $this->belongsTo('App\Models\Setup\Project');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
        //  return $this->belongsTo(User::class);
    }

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

    public function cocApplications()
    {
        return $this->hasMany(CocApplication::class);
    }

    public function employeeCertificate()
    {
        return $this->belongsTo('App\Models\EmployeeCertificate');
    }

    public function travelOrders()
    {
        return $this->hasMany(TravelOrder::class);
    }

    public function dtrs()
    {
        return $this->hasMany(Dtr::class, 'employee_dtr_no', 'employee_no');
    }

    public function overtimeOrderDetails()
    {
        return $this->hasMany(OvertimeOrderDetail::class);
    }

    public function overtimeOrder()
    {
        return $this->hasMany(OvertimeOrder::class, 'created_by');
    }
}
