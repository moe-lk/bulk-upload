<?php


namespace App\Providers;


use App\Libraries\ShaHash\SHAHasher;
use Illuminate\Hashing\HashServiceProvider;

class ShaHashServiceProvider extends HashServiceProvider
{

    public function register()
    {
        $this->app->singleton('hash',function (){
           return new SHAHasher;
        });
    }

}
