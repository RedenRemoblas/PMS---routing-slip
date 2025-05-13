<?php

namespace App\Filament\Resources\Hr\DtrResource\Pages;

use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\View\View;


class ViewDtr extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static string $view = 'filament.resources.hr.dtr-resource.pages.view-dtr';
    protected static ?string $title = 'DTR Format';

    public $month;

    public function showDtr() {}
}
