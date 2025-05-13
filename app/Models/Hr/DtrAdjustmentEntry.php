<?php

namespace App\Models\Hr;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DtrAdjustmentEntry extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'dtr_adjustment_entries';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'request_id',
        'adjustment_datetime',
        'logType',
        'reason',
        'remarks',
    ];

    /**
     * Relationships.
     */

    // Link to the parent adjustment request
    public function request()
    {
        return $this->belongsTo(DtrAdjustmentRequest::class, 'request_id');
    }
}
