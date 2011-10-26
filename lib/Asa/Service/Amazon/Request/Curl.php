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

class Asa_Service_Amazon_Request_Curl extends Asa_Service_Amazon_Request_Abstract 
{
    
    public function send(array $url_params)
    {
        $this->build($url_params);
                
        if (!$request = $this->getRequest()) {
			return false;
		}
		
		$config = array(
            'adapter' => 'AsaZend_Http_Client_Adapter_Curl',
        );
            
        $client = new AsaZend_Http_Client($request, $config);

        $response = $client->request('GET');
        
        if ($response->isSuccessful()) {
            $this->_response = $response->getBody();
        }
				
		return $this->_response;
    }
}
?>