<?php
namespace losthost\telle\globals;
use losthost\telle\Env;

function __($string, $vars=[]) {
    global $lang;
    
    if (isset($lang[Env::$language_code]) && isset($lang[Env::$language_code][$string])) {
        $string = $lang[Env::$language_code][$string];
    }
    
    foreach ($vars as $key => $value) {
        $string = str_replace("%$key%", $value, $string);
    }
    
    return $string;
}

