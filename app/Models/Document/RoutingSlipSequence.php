<?php

namespace App\Models\Document;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingSlipSequence extends Model
{
    protected $fillable = [
        'routing_slip_id',
        'user_id',
        'admin_type',
        'sequence_number',
        'status',
        'remarks',
        'acted_at',
        'division',
        'position',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function routingSlip(): BelongsTo
    {
        return $this->belongsTo(RoutingSlip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
