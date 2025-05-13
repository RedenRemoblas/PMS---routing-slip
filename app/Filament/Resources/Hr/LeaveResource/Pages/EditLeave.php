<?php

namespace App\Filament\Resources\Hr\LeaveResource\Pages;

use App\Models\Hr\Leave;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Hr\LeaveResource;
use Illuminate\Validation\ValidationException;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;
}
