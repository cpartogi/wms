<?php

function generate_code($length = 32)
{
    $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $numbers    = "0123456789";
    $mixed      = str_split($characters . $numbers);
    
    $temp = "";
    
    for ($i = 0; $i < $length; $i++) {
        $temp .= $mixed[rand(0, count($mixed) - 1)];
    }
    
    return $temp;
}

/**
 * @param $s is your string you search from
 * @param $i is your input
 * @return bool
 */
function string_contains($s, $i)
{
    $s = strtolower($s);
    $i = strtolower($i);
    
    if (strpos($s, $i) !== false) {
        return true;
    }
    
    return false;
}