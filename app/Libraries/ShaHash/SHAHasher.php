<?php


namespace App\Libraries\ShaHash;


use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Hashing\HashManager;

class SHAHasher  extends HashManager implements HasherContract
{


    public function check($value, $hashedValue, array $options = [])
    {
        return password_verify($value, $hashedValue);
    }


    public function make($value, array $options = [])
    {
        return password_hash($value, PASSWORD_DEFAULT, $options);
    }




    public function needsRehash($hashedValue, array $options = [])
    {
        return password_needs_rehash($hashedValue, $options);
    }


    public function info($hashedValue)
    {
        // TODO: Implement info() method.
        return $hashedValue;
    }

}
