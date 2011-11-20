<?php
/**
 * AmazonSimpleAdmin - Wordpress Plugin
 * 
 * @author Timo Reith
 * @copyright Copyright (c) 2007-2011 Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * 
 */

require_once 'Asa/Service/Amazon/Request/Abstract.php';

class Asa_Service_Amazon_Request_Socket extends Asa_Service_Amazon_Request_Abstract 
{
    
    public function _send($request_url)
    {		
		$config = array(
    		// socket is default, but for clarity
            'adapter' => 'AsaZend_Http_Client_Adapter_Socket', 
        );
            
        $client = new AsaZend_Http_Client($request_url, $config);

        return $client->request('GET');
    }
}
?>