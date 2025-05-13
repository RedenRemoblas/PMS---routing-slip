<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LeaveDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_id',
        'leave_date',
        'period',
        'qty',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $query = static::where('leave_id', $model->leave_id)
                ->where('leave_date', $model->leave_date);

            if ($model->exists) {
                $query->where('id', '!=', $model->id);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'leave_date' => 'The combination of leave date and leave id must be unique.',
                ]);
            }
        });
    }

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }
}
