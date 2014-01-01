<?php
/**
 * AmazonSimpleAdmin - Wordpress Plugin
 * 
 * @author Timo Reith
 * @copyright Copyright (c) Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * 
 */

class Asa  
{
    function __construct() 
    {
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $var
     */
    public static function debug($var)
    {
        $path = getenv('ASA_DEBUG_PATH');
        
        if ( empty($path) || !is_dir($path) || !is_writable($path) ) {
            return false;
        }
        
        $bt     = debug_backtrace();
        $info   = pathinfo($bt[0]['file']);

        $out = 
            'File: '. $info['basename'] . PHP_EOL .
            'Line: '. $bt[0]['line'] . PHP_EOL .
            'Time: '. date('Y/m/d H:i:s') . PHP_EOL .
            'Type: '. gettype($var) . PHP_EOL . PHP_EOL;
        
        if (is_array($var) || is_object($var)) {
            $out .= print_r($var, true);
        } elseif (is_bool($var)) {
            $out .= var_export($var, true);
        } else {
            $out .= $var;
        }
        
        $out .= 
            PHP_EOL . PHP_EOL .
            '-------------------------------------------------------'. 
            PHP_EOL . PHP_EOL;
        
        
        file_put_contents($path . date('YmdHis') . '.txt', $out);
    }
}


?>