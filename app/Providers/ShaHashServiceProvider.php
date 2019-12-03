<?php


namespace App\Providers;


use App\Libraries\ShaHash\SHAHasher as SHAHasher;
use Illuminate\Hashing\HashServiceProvider;

class ShaHashServiceProvider extends HashServiceProvider
{

    public function register()
    {
        $this->app->singleton('hash',function (){
            return new SHAHasher($this->app);
        });
    }

    public function provides() {
        return array('hash');
    }


}
