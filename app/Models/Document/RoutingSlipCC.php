<?php

namespace App\Models\Document;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingSlipCC extends Model
{
    protected $fillable = [
        'routing_slip_id',
        'user_id',
        'name',
        'position',
        'division',
        'email',
        'remarks',
    ];

    public function routingSlip(): BelongsTo
    {
        return $this->belongsTo(RoutingSlip::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the name attribute
     * 
     * @param string|null $value
     * @return string
     */
    public function getNameAttribute($value)
    {
        // If name is not set but user_id is, get name from user relationship
        if (empty($value) && $this->user_id) {
            return $this->user->name ?? '';
        }
        
        return $value ?? '';
    }
    
    /**
     * Set the name attribute
     * 
     * @param string|null $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value ?? '';
    }
}