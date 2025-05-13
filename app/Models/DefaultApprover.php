<?php

namespace App\Models;

use App\Models\Setup\Division;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultApprover extends Model
{
    use HasFactory;

    // Specify the table if it's not the plural form of the model name
    protected $table = 'default_approvers';

    // Define the fillable properties
    protected $fillable = [
        'employee_id',
        'sequence',
        'division_id',
    ];

    // Define relationships

    /**
     * Get the employee associated with the default approver.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the division associated with the default approver.
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
