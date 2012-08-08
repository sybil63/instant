<?php
/**
 * Get the value of the name from the request _GET | _POST
 * Set status to 400 if no value is find and the default value is null
 * 
 * @param $name
 * @param $default The default value of the $name
 * @return $value
 **/
function _get_argument($name, $default = null) {
    $values = $this->_get_arguments($name);
    $len = count($values);
    if ( 0 < $len ) {
        return $values[$len - 1];
    }
    if (null == $default) {
        die("Missing argument $name");
    }
    return $default;
}

function _get_arguments($name)
{
    $values = array();
    if ( isset($_GET[$name]))
    {
        $values[] = $_GET[$name];
    }
    if ( isset($_POST[$name]))
    {
        $values[] = $_POST[$name];
    }
    return $values;
}

function starts_with($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function ends_with($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return (substr($haystack, -$length) === $needle);
}

