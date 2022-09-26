<?php

/**
 * Converts multi dimensional arrays to single level array
 * @param array $array
 * @param string|null $parentKey
 * @param string $separator
 * @return array|false
 */
function flattenArrayKeys(array $array, string $parentKey = null, string $separator = '-'):array {
    $result = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, flattenArrayKeys($value, ($parentKey ? $parentKey . $separator:'') . $key));
        }
        else {
            $result[($parentKey ? $parentKey . $separator:'') . $key] = $value;
        }
    }
    return $result;
}

/**
 * Case-insensitive search for present array key value
 * @param string $needle
 * @param array $haystack
 * @return mixed|bool The present key value, or false
 */
function insensitiveKeyValue(string $needle, array $haystack) {
    foreach ($haystack as $key => $value) {
        if (strtolower($needle) == strtolower($key)) {
            return $value;
        }
    }
    return false;
}

/**
 * @param string $s space case|snake_case|kebab-case|dot.case|Mix-e.d_Case
 * @return string In-Pretty-Case
 */
function prettyCase(string $s): string {
    $s = strtolower($s);
    $separators = [' ', '_', '-', '·'];
    foreach ($separators as $sep) {
        if(strpos($s, $sep) !== false) {
            $s = implode('-', array_map('ucfirst', explode($sep, $s)));
        }
    }
    if (strlen($s) <= 3) {
        $s = strtoupper($s);
    }
    return ucfirst($s);
}

/**
 * @param string $countryCode
 * @return string
 */
function getCountryFlagEmoji(string $countryCode)
{
    if (strlen($countryCode) !== 2) {
        throw new \InvalidArgumentException('Please provide a 2 character country code.');
    }
    $countryCode = strtoupper($countryCode);
    return implode('', array_map(function($s){
        $i = ord($s) + 127397;
        return mb_convert_encoding('&#'. $i .';', 'UTF-8', 'HTML-ENTITIES');
    }, str_split($countryCode)));
}
