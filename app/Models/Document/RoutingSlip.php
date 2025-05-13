<?php

namespace App\Models\Document;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingSlip extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'remarks',
        'status',
        'created_by',
        'document_type',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(RoutingSlipFile::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(RoutingSlipSequence::class)->orderBy('sequence_number');
    }
    
    public function ccRecipients(): HasMany
    {
        return $this->hasMany(RoutingSlipCC::class);
    }
}
