<?php
// Reads settings from real environment variables first (used on hosts),
// then from the local .env file (used on XAMPP).

if(!function_exists('env')){

    function env($key, $default = null){

        static $vars = null;

        if($vars === null){
            $vars = [];
            $file = __DIR__ . '/../.env';

            if(is_readable($file)){
                foreach(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line){
                    $line = trim($line);

                    if($line === '' || $line[0] === '#' || strpos($line, '=') === false){
                        continue;
                    }

                    [$k, $v] = explode('=', $line, 2);
                    $vars[trim($k)] = trim($v, " \t\"'");
                }
            }
        }

        $real = getenv($key);

        if($real !== false && $real !== ''){
            return $real;
        }

        return $vars[$key] ?? $default;
    }
}