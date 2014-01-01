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
    
    /**
     * Fatory for Asa_Service_Amazon_Request_Abstract
     * @param Asa_Service_Amazon $asa
     * @return Asa_Service_Amazon_Request_Abstract
     */
    public static function factory(Asa_Service_Amazon $asa)
    {
        $request = null;
        
        try {
            require_once 'Asa/Service/Amazon/Request/Rest.php';
            $request = new Asa_Service_Amazon_Request_Rest($asa);
        } catch (Exception $e1) {
            
            try {
                if (function_exists('curl_init')) {
                    // if curl exists
                    require_once 'Asa/Service/Amazon/Request/Curl.php';
                    $request = new Asa_Service_Amazon_Request_Curl($asa);
                } else {
                    // else socket
                    require_once 'Asa/Service/Amazon/Request/Socket.php';
                    $request = new Asa_Service_Amazon_Request_Socket($asa);            
                }
            } catch (Exception $e2) {
                throw $e1; 
            }
        }
        
        return $request;
    }
}
?>