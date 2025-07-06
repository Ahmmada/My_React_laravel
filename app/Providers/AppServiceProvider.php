<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL; // **أضف هذا السطر
use Inertia\Inertia;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    
    
    

// في boot()

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
Inertia::share('auth', function () {
    $user = auth()->user();

    return [
        'user' => $user,
        'permissions' => $user ? $user->getPermissionNames() : [],
    ];
});
       View::share('csrf_token', csrf_token());
        Carbon::macro('toArabicTime', function () {
    return $this->format('h:i') . ' ' . ($this->hour >= 12 ? 'م' : 'ص');
});
  
        // تحقق من وجود رأس X-Forwarded-Proto وإذا كان https
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }
    }
}


