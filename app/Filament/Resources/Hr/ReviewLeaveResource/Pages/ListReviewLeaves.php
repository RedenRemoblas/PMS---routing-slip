<?php

namespace App\Filament\Resources\Hr\ReviewLeaveResource\Pages;

use App\Filament\Resources\Hr\ReviewLeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReviewLeaves extends ListRecords
{
    protected static string $resource = ReviewLeaveResource::class;
    protected ?string $subheading = 'List of Leaves for Review and Approval';

  
}
