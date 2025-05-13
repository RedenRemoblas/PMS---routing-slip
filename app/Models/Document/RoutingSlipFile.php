<?php

namespace App\Models\Document;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RoutingSlipFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'routing_slip_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_by',
        'file_type',
        'remarks',
    ];

    protected $appends = ['download_url'];

    public function getDownloadUrlAttribute(): string
    {
        // Generate a route that will force the download with proper headers
        return route('file.download', ['id' => $this->id]);
    }
    public function routingSlip(): BelongsTo
    {
        return $this->belongsTo(RoutingSlip::class);
    }
    
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
