<?php

use App\Jobs\AccrueMonthlyLeave;
use App\Jobs\HandleLeaveExpiration;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->booted(function (Application $app) {
        Log::info('Laravel Booted: Executing jobs once and setting up scheduled tasks.');

        // ✅ Run both jobs immediately once when Laravel boots

        //    dispatch(new AccrueMonthlyLeave());
        //   dispatch(new HandleLeaveExpiration());



        // ✅ Run both jobs immediately once when Laravel boots (Synchronous)
        AccrueMonthlyLeave::dispatchSync();
        HandleLeaveExpiration::dispatchSync();


        $schedule = $app->make(Schedule::class);

        // ✅ Schedule AccrueMonthlyLeave to run monthly on the 1st at midnight
        $schedule->job(new AccrueMonthlyLeave())->monthlyOn(1, '00:00');

        // ✅ Schedule HandleLeaveExpiration to run daily at 1 AM
        $schedule->job(new HandleLeaveExpiration())->dailyAt('01:00');
    })
    ->create();
