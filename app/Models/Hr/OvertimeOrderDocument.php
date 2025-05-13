<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OvertimeOrderDocument extends Model
{
    use HasFactory;

    protected $fillable = ['overtime_order_id', 'file_path', 'file_name'];

    public function overtimeOrder()
    {
        return $this->belongsTo(OvertimeOrder::class);
    }
}
