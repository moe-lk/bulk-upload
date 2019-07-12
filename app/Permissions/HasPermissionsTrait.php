<?php


namespace App\Permissions;

use App\Models\Security_group_user;

trait HasPermissionsTrait
{
    public function roles(){
        return $this->belongsToMany(Security_group_user::class,'security_group_users');
    }


    public function hasRole( ... $roles ) {
        foreach ($roles as $role) {
            if ($this->roles->contains('code', $role)) {
                return true;
            }
        }
        return false;
    }


}