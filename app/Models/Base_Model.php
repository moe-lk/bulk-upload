<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Base_Model extends Model{



    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';


    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->created_user_id = 1;
        });
    }

}