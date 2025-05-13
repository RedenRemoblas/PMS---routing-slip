<?php

namespace App\Models\Hr;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeOrderDetail extends Model
{
    protected $fillable = [
        'overtime_order_id',
        'employee_id',
        'position',
        'division',
        'hours_rendered',
    ];

    /**
     * Get the related overtime order.
     */
    public function overtimeOrder(): BelongsTo
    {
        return $this->belongsTo(OvertimeOrder::class);
    }

    /**
     * Get the related employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
