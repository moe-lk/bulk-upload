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

function clean($string) {
   $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

   return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
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

function unique_multidim_array(array $array, $key) {
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



function merge_two_arrays($array1,$array2) {

    $data = array();
    $arrayAB = array_merge($array1,$array2);

    foreach ($arrayAB as $value) {
        dd($arrayAB);
        $id = $value['row'];
        if (!isset($data[$id])) {
            $data[$id] = array();
        }
        $data[$id] = array_merge($data[$id],$value);
    }
    return $data;
}


function merge_error_by_row($errors,$key){
    $temp_array = array();
    $i = 0;

    foreach($errors as $keys => $val) {
        if (!in_array($val[$key], $temp_array)) {
            $temp_array[$keys]['errors'][] = $val;
        }
        $i++;
    }
    return $temp_array;
}

/**
 * @param $error
 * @param $count
 * @param $reader
 * bind error messages to the excel file
 */

function append_errors_to_excel($error, $count, $reader){
    $active_sheet = $reader->getActiveSheet();
    $prev_value = $active_sheet->getCell('A'.$error['row'])->getValue();
    $active_sheet->setCellValue('A'. ($error['row']) ,  $prev_value.','.implode(',',$error['errors']));
    $active_sheet->getStyle('A'. ($error['row']))->getAlignment()->setWrapText(true);

    $columns = Illuminate\Support\Facades\Config::get('excel.columns');

    $column = array_keys($columns,$error['attribute']);
    if(!empty($column)){
        $column = $column[0]+1;
        $selectedCells = $active_sheet->setSelectedCellByColumnAndRow($column,$error['row']);
        $active_cell = ($selectedCells->getActiveCell());

        $active_sheet->getStyle($active_cell)
            ->getFill()
            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF0000');

        }

}

function colorizeCell($column,$error,$active_sheet){
    $column = array_keys($column,$error['attribute']);
    $selectedCells = $active_sheet->setSelectedCellByColumnAndRow($column,$error['row']);
    $active_cell = ($selectedCells->getActiveCell());

    $active_sheet->getStyle($active_cell)
        ->getFill()
        ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setARGB('FF0000');

}


function errors_unique_array($item,$key){

        $search = array_filter($item,function ($data) use ($item){
            return isset($data['row']) &&  ($data['row']  == $item->row());
        });

        if($search){
            array_push($search[0]['errors'],implode(',',$item->errors()));
            $errors = $search;
        }

        return $errors;
}

function isEmptyRow($row) {
    foreach($row as $cell){
        if (null !== $cell) return false;
    }
    return true;
}
