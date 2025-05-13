<?php

namespace App\Models\Hr;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dtr extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'employee_no',
        'employee_dtr_no',
        'device_serial_no',
        'verify_mode',
        'dtr_timestamp',
        'log_type',
        'remarks',
        'seuqence_no',
        'deleted_at',

    ];

    protected $casts = [
        'dtr_timestamp' => 'datetime',
    ];



    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_dtr_no', 'employee_no');
    }
}
