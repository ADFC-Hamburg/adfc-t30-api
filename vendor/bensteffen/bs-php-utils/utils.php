<?php 

// namespace bensteffen\utils;

function stringStart($string, $query) {
    return substr($string, 0, strlen($query)) === $query;
}

function jsenc($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
}

function isEmpty($array) {
    return count($array) === 0;
}

function setFieldDefault($array, $key, $default) {
    if (!array_key_exists($key, $array)) {
        $array[$key] = $default;
    }
    return $array;
}

function isAssoc(array $arr) {
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function assocShift(&$arr) {
    list($firstKey) = array_keys($arr);
    $removed = [ $firstKey, $arr[$firstKey] ];
    unset($arr[$firstKey]);
    return $removed;
}

function extractByKey($key, $array) {
    $output = [];
    if ($array !== null) {
        foreach($array as $assoc) {
            array_push($output, $assoc[$key]);
        }
    }
    return $output;
}



function extractValues($keys, $data) {
    $values = [];
    foreach($keys as $k) {
        if (array_key_exists($k, $data)) {
            array_push($values, $data[$k]);
        }
    }
    return $values;
}

function extractArray($keys, $baseArray, $flatten = false) {
    $extracted = [];
    foreach($keys as $k) {
        if (array_key_exists($k, $baseArray)) {
            $extracted[$k] = $baseArray[$k];
        }
    }
    if ($flatten && count($extracted) === 1) {
        $extracted = array_pop($extracted);
    }
    return $extracted;
}

function arrayFilter($array, $key, $value) {
    $output = [];
    foreach ($array as $elementKey => $elementArray) {
        if ($elementArray[$key] === $value) {
            $output[$elementKey] = $elementArray;
        }
    }
    return $output;
}

function arrayIgnore($array, $valuesToIgnore) {
    $output = [];
    foreach ($array as $v) {
        if (!in_array($v, $valuesToIgnore)) {
            array_push($output, $v);
        }
    }
    return $output;
}

function assocIgnore($array, $keysToIgnore) {
    $output = [];
    foreach ($array as $k => $v) {
        if (!in_array($k, $keysToIgnore)) {
            $output[$k] = $v;
        }
    }
    return $output;
}

?>