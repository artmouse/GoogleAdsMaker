<?php
namespace Adson\Middleware;

class Validator
{
    public static function clearArray(array $array)
    {
        $result = array();
        if(is_array($array)) {
            foreach ($array as $key => $value) {
                $result[$key] = trim(htmlspecialchars(strip_tags(($value))));
            }
        }
        return $result;
    }
}