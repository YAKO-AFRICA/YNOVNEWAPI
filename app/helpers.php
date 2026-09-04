<?php
// helper.php
function Refgenerate($table, $prefix, $key)
{
    $latest = $table::orderBy('id', 'desc')->first();
    if (!$latest || !isset($latest->$key)) {
        return $prefix . '-00001';
    }

    $number = preg_replace("/[^0-9]/", '', $latest->$key);

    if (empty($number)) {
        $number = 0;
    }

    return $prefix . '-' . sprintf('%05d', $number + 1);
}

function RefgenerateCode($table, $init, $key)
{
    $latest = $table::orderBy('id', 'desc')->first();
    if (!$latest) {
        $code = $init . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3)) . rand(10, 99);
        return $code;
    }

    $string = preg_replace("/[^0-9\.]/", '', $latest->$key);
    $code = $init . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3)) . rand(10, 99);
    return $code;
}


