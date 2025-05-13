<?php

namespace App\Providers;

use App\Http\Middleware\CheckRole;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as FilamentLoginResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::middleware('role', CheckRole::class);
    }

    public function register()
    {
        // Bind the custom login response
        //  $this->app->bind(FilamentLoginResponse::class, \App\Http\Responses\CustomLoginResponse::class);

    }
}
