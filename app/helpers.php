<?php

function get_l_name($name){
    $name = trim($name);
    $last_name = (strpos($name,' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    return $last_name;
}

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

function isEmptyRow($row) {
    foreach($row as $cell){
        if (null !== $cell) return false;
    }
    return true;
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

function array_value_recursive($key, array $arr){
    $val = array();
    array_walk_recursive($arr, function($v, $k) use($key, &$val){
        if($k == $key) array_push($val, $v);
    });
    return count($val) > 1 ? $val : array_pop($val);
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
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF0000');

        }
}

function rows($error){
    return $error['row'];
}

function rowIndex($row){
    return $row->getRowIndex();
}

function removeRows($row,$param){
    if(in_array($row,$param['rows'])){
        $param['reader']->getActiveSheet()->removeRow($row);
    }
}

function colorizeCell($column,$error,$active_sheet){
    $column = array_keys($column,$error['attribute']);
    $selectedCells = $active_sheet->setSelectedCellByColumnAndRow($column,$error['row']);
    $active_cell = ($selectedCells->getActiveCell());

    $active_sheet->getStyle($active_cell)
        ->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
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

function sig_handler($signo){
    global $child;
    switch($signo){
        case 'SIFCLD':
    }

}
function processParallel($func ,array $arr, $procs = 4,$params =[])
    {
        // Break array up into $procs chunks.
        $chunks   = array_chunk($arr, ceil((count($arr) / $procs)));
        $pid      = -1;
        $children = array();
        foreach ($chunks as $items) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                die('could not fork');
            } else if ($pid === 0) {
                // We are the child process. Pass a chunk of items to process.
                echo('['.getmypid().']This Process executed at'.date("F d, Y h:i:s A")."\n") ;
                array_walk($items, $func,$params);
                exit(0);
            } else {
                // We are the parent.
                echo('['.getmypid().']This Process executed at'.date("F d, Y h:i:s A")."\n") ;
                $children[] = $pid;
            }
        }
        // Wait for children to finish.
        foreach ($children as $pid) {
            // We are still the parent.
            pcntl_waitpid($pid, $status);
        }
    }
