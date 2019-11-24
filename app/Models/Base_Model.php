<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Base_Model extends Model{



    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';


    public static function boot()
    {

        parent::boot();
        self::creating(function ($model) {
            $model->created = Carbon::now();
        });
        self::updating(function ($model) {
            $model->modified = Carbon::now();
        });
    }

}
