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


function getMatchingKeys($array){
    $keys = [];
    foreach ($array as $key => $value){
        if(strstr($key , 'option'))
            $keys[] = $key;
    }
    return $keys;
}

function is_sha1($str) {
    return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
}

function isEmpty($value){
    return $value['institution_optional_subject'] !== null;
}

function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}
