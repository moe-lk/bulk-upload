<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() == 'local') {
            $this->app->register('\Laracademy\ModelGenerator\ModelGeneratorServiceProvider');
        }
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
     public function boot()
     {
         //
         if(!(\App::environment('local'))) {
             URL::forceScheme('https');
         }
     }
}
