<?php

// Gen name with initials with help of fullname
function genNameWithInitials($fullname = null){
    $names = explode(' ', $fullname);
    $length  = count($names);
    $Initials = '';
    if($length > 1){
        for ($i = 0; ($length-1) > $i; $i++) {
            $Initials = $Initials . '' . mb_substr($names[$i], 0, 1, "UTF-8");
        }
        $nameWithInitials = $Initials . ' ' . $names[$length - 1];
    }else{
        $nameWithInitials = $fullname;
    }
    return $nameWithInitials;
}

//check the array of keys exists in the given array
function array_keys_exists(array $keys, array $arr)
{
    return !array_diff_key(array_flip($keys), $arr);
}
