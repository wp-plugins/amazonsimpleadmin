<?php
/**
 * AmazonSimpleAdmin - Wordpress Plugin
 * 
 * @author Timo Reith
 * @copyright Copyright (c) 2007-2011 Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * 
 */

require_once 'Asa/Service/Amazon/Request/Exception.php';

abstract class Asa_Service_Amazon_Request_Abstract
{
    /**
     * Asa_Service_Amazon object
     * @var Asa_Service_Amazon
     */
    protected $_asa;
    
    /**
     * The request url
     * @var string
     */
    protected $_request_url;
    
    /**
     * The response
     * @var string
     */
    protected $_response;
    
    
    
    /**
     * Constructor
     * @param Asa_Service_Amazon $asa
     */
    public function __construct(Asa_Service_Amazon $asa) 
    {
        $this->_asa = $asa;        
    }
    
    /**
     * Sends the built request url and fetches the result
     * @param array
     * @return string
     */
    abstract public function send(array $url_params);
    
    /**
     * Builds the request url
     * @param array $url_params
     * @throws Asa_Service_Exception
     */    
    public function build(array $url_params)
    {
        if (empty($url_params) && !is_array($url_params)) {
			throw new Asa_Service_Exception('Invalid URL params');
		}

	    if ($this->_asa->getLocale() == null) {
		    throw new Asa_Service_Exception('Missing locale');
		}		
		
		$this->_request_url = '';
		
		$url_parts = array();
		foreach (array_keys($url_params) as $key) {
			$url_parts[] = $key .'='. $url_params[$key];
		}
		sort($url_parts);
		
		$base_url = Asa_Service_Amazon::getEndpoint($this->_asa->getLocale()) .		
			'?Service=AWSECommerceService&AWSAccessKeyId=%s&Version=%s&AssociateTag=%s&';
			
		$base_url = sprintf($base_url, $this->_asa->getAccessKeyId(), 
		    Asa_Service_Amazon::$api_version, $this->_asa->getAssociateTag());

		// append params to base url
		$url = $base_url . implode('&', $url_parts);
		
        // get the host
		$host = parse_url($url, PHP_URL_HOST);
		
		// add timestamp
		$timestamp = gmstrftime('%Y-%m-%dT%H:%M:%S.000Z');
		$url .= '&Timestamp='. $timestamp;

		// sort the params
		$paramstart = strpos($url, '?');
		$workurl = substr($url, $paramstart+1);
		$workurl = str_replace(',', '%2C', $workurl);
		$workurl = str_replace(':', '%3A', $workurl);
		$params = explode('&', $workurl);
		sort($params);
		
		// prepare the sign string
		$signstr = "GET\n". $host ."\n/onca/xml\n". implode('&', $params);
		$signstr = base64_encode(
		    hash_hmac('sha256', $signstr, $this->_asa->getAccessKeySecret(), true));
		$signstr = urlencode($signstr);
		
		// add the signature to the url
		$signurl = $url . '&Signature='. $signstr;
		
		// set the request
		$this->_request_url = $signurl;
		
		return true;	                
    }
    
    public function getRequest()
    {
        return $this->_request_url;
    }
    
    public function getResponse()
    {
        return $this->_response;
    }
}


?>