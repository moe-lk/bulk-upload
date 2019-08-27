<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class User_identity extends Model  {
    
    public const CREATED_AT = 'created';
    public const UPDATED_AT = 'modified';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_identities';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['identity_type_id', 'number', 'issue_date', 'expiry_date', 'issue_location', 'comments', 'security_user_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['modified', 'created', 'issue_date', 'expiry_date', 'modified', 'created'];
    

}