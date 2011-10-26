<?php
/**
 * AmazonSimpleAdmin - Wordpress Plugin
 * 
 * @author Timo Reith
 * @copyright Copyright (c) 2007-2011 Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * 
 */

abstract class Asa_Service_Amazon_Request  
{

    private function __construct() {}
    
    public static function factory(Asa_Service_Amazon $asa)
    {
        if (function_exists('curl_init')) {
            // if curl exists
            require_once 'Asa/Service/Amazon/Request/Curl.php';
            $request = new Asa_Service_Amazon_Request_Curl($asa);
        } else {
            // else socket
            require_once 'Asa/Service/Amazon/Request/Socket.php';
            $request = new Asa_Service_Amazon_Request_Socket($asa);            
        }
        
        return $request;
    }
}
?>